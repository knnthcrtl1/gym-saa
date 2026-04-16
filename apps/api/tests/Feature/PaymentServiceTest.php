<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\PayMongoService;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_table_includes_paymongo_columns(): void
    {
        $this->assertTrue(Schema::hasTable('payment_webhooks'));
        $this->assertTrue(Schema::hasColumns('payments', [
            'gateway',
            'currency',
            'gateway_checkout_session_id',
            'gateway_payment_id',
            'gateway_reference',
            'checkout_url',
            'gateway_metadata',
            'raw_response',
            'paid_at',
        ]));
    }

    public function test_create_checkout_payment_persists_pending_paymongo_payment(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'subscription' => $subscription] = $this->createBillingFixture();

        $this->mock(PayMongoService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'id' => 'cs_test_123',
                    'checkout_url' => 'https://checkout.paymongo.test/session/cs_test_123',
                    'reference_number' => 'GYM-PAY-1',
                    'metadata' => ['payment_id' => '1'],
                    'response' => ['data' => ['id' => 'cs_test_123']],
                ]);
        });

        $service = $this->app->make(PaymentService::class);

        $result = $service->createCheckoutPayment([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'amount' => 1500,
            'currency' => 'php',
            'notes' => 'April renewal',
        ], null);

        $this->assertSame('https://checkout.paymongo.test/session/cs_test_123', $result['checkout_url']);
        $this->assertSame('paymongo', $result['payment']->gateway);
        $this->assertSame('pending', $result['payment']->status);
        $this->assertSame('PHP', $result['payment']->currency);
        $this->assertSame('cs_test_123', $result['payment']->gateway_checkout_session_id);
        $this->assertSame('GYM-PAY-1', $result['payment']->reference_no);

        $this->assertDatabaseHas('payments', [
            'id' => $result['payment']->id,
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'gateway' => 'paymongo',
            'currency' => 'PHP',
            'payment_method' => 'online',
            'status' => 'pending',
            'gateway_checkout_session_id' => 'cs_test_123',
            'gateway_reference' => 'GYM-PAY-1',
            'reference_no' => 'GYM-PAY-1',
            'checkout_url' => 'https://checkout.paymongo.test/session/cs_test_123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'actor_id' => null,
            'action' => 'payments.checkout_created',
            'auditable_type' => Payment::class,
            'auditable_id' => $result['payment']->id,
        ]);
    }

    public function test_manual_gcash_payment_with_proof_stays_pending_until_reviewed(): void
    {
        Storage::fake('public');

        [
            'tenant' => $tenant,
            'branch' => $branch,
            'member' => $member,
            'subscription' => $subscription,
            'actor' => $actor,
        ] = $this->createBillingFixture();

        $service = $this->app->make(PaymentService::class);

        $payment = $service->recordManualPayment([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_method' => 'gcash',
            'reference_no' => 'GCASH-1001',
            'notes' => 'Paid via owner QR',
        ], $actor, UploadedFile::fake()->image('receipt.jpg'));

        $this->assertSame('pending', $payment->status);
        $this->assertSame('pending', $payment->verification_status);
        $this->assertNull($payment->paid_at);
        $this->assertCount(1, $payment->proofs);
        Storage::disk('public')->assertExists($payment->proofs->first()->path);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'actor_id' => $actor->id,
            'action' => 'payments.manual_recorded',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
        ]);

        $subscription->refresh();
        $this->assertSame('unpaid', $subscription->payment_status);
        $this->assertSame('pending', $subscription->status);
    }

    public function test_verifying_manual_bank_transfer_marks_payment_paid_and_activates_subscription(): void
    {
        Storage::fake('public');

        [
            'tenant' => $tenant,
            'branch' => $branch,
            'member' => $member,
            'subscription' => $subscription,
            'actor' => $actor,
        ] = $this->createBillingFixture();

        $service = $this->app->make(PaymentService::class);

        $payment = $service->recordManualPayment([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_method' => 'bank_transfer',
            'reference_no' => 'BANK-2001',
        ], $actor, UploadedFile::fake()->create('transfer.pdf', 120, 'application/pdf'));

        $reviewedPayment = $service->verifyManualPayment($payment->fresh(), $actor, 'Confirmed in bank ledger');

        $this->assertSame('paid', $reviewedPayment->status);
        $this->assertSame('verified', $reviewedPayment->verification_status);
        $this->assertSame($actor->id, $reviewedPayment->reviewed_by);
        $this->assertSame('Confirmed in bank ledger', $reviewedPayment->review_notes);
        $this->assertNotNull($reviewedPayment->paid_at);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'actor_id' => $actor->id,
            'action' => 'payments.verified',
            'auditable_type' => Payment::class,
            'auditable_id' => $reviewedPayment->id,
        ]);

        $subscription->refresh();
        $this->assertSame('paid', $subscription->payment_status);
        $this->assertSame('active', $subscription->status);
    }

    public function test_rejecting_manual_payment_keeps_subscription_unpaid(): void
    {
        Storage::fake('public');

        [
            'tenant' => $tenant,
            'branch' => $branch,
            'member' => $member,
            'subscription' => $subscription,
            'actor' => $actor,
        ] = $this->createBillingFixture();

        $service = $this->app->make(PaymentService::class);

        $payment = $service->recordManualPayment([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_method' => 'gcash',
        ], $actor, UploadedFile::fake()->image('failed-receipt.png'));

        $reviewedPayment = $service->rejectManualPayment($payment->fresh(), $actor, 'Reference did not match');

        $this->assertSame('failed', $reviewedPayment->status);
        $this->assertSame('rejected', $reviewedPayment->verification_status);
        $this->assertSame('Reference did not match', $reviewedPayment->review_notes);
        $this->assertNull($reviewedPayment->paid_at);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'actor_id' => $actor->id,
            'action' => 'payments.rejected',
            'auditable_type' => Payment::class,
            'auditable_id' => $reviewedPayment->id,
        ]);

        $subscription->refresh();
        $this->assertSame('unpaid', $subscription->payment_status);
        $this->assertSame('pending', $subscription->status);
    }

    private function createBillingFixture(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Fitness',
            'slug' => 'acme-fitness',
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'status' => 'active',
        ]);

        $actor = User::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Gym Admin',
            'email' => 'admin+'.uniqid().'@example.test',
            'password' => 'password',
            'role' => 'gym_admin',
            'status' => 'active',
        ]);

        $member = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-1001',
            'first_name' => 'Pat',
            'last_name' => 'Lee',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        $plan = MembershipPlan::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Monthly Unlimited',
            'duration_type' => 'month',
            'duration_value' => 1,
            'price' => 1500,
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'amount' => 1500,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        return compact('tenant', 'branch', 'actor', 'member', 'plan', 'subscription');
    }
}