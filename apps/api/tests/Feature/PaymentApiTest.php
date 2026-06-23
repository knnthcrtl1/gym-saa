<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GymPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_payment_request_is_idempotent_with_same_key(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'subscription' => $subscription, 'actor' => $actor] = $this->createBillingFixture();

        Sanctum::actingAs($actor);

        $payload = [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_method' => 'cash',
            'reference_no' => 'CASH-1001',
            'notes' => 'Walk-in renewal',
            'status' => 'paid',
        ];

        $headers = ['X-Idempotency-Key' => 'payment-manual-001'];

        $first = $this->postJson('/api/v1/payments/manual', $payload, $headers)
            ->assertCreated();

        $second = $this->postJson('/api/v1/payments/manual', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Payment already recorded for this idempotency key.');

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_reusing_idempotency_key_with_different_payload_returns_conflict(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'subscription' => $subscription, 'actor' => $actor] = $this->createBillingFixture();

        Sanctum::actingAs($actor);

        $headers = ['X-Idempotency-Key' => 'payment-manual-002'];

        $this->postJson('/api/v1/payments/manual', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_method' => 'cash',
            'status' => 'paid',
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/payments/manual', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1750,
            'payment_method' => 'cash',
            'status' => 'paid',
        ], $headers)
            ->assertStatus(409)
            ->assertJsonPath('message', 'This idempotency key has already been used for a different request payload.');
    }

    public function test_payment_audit_logs_endpoint_lists_recorded_events(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'subscription' => $subscription, 'actor' => $actor] = $this->createBillingFixture();

        Sanctum::actingAs($actor);

        $payment = $this->postJson('/api/v1/payments/manual', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_method' => 'cash',
            'status' => 'paid',
        ])->assertCreated()->json('data');

        $this->getJson('/api/v1/payments/'.$payment['id'].'/audit-logs')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'payments.manual_recorded')
            ->assertJsonPath('data.0.actor_id', $actor->id);
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
            'staff_role' => 'owner',
            'status' => 'active',
            'permissions' => [
                GymPermission::PAYMENTS_VIEW,
                GymPermission::PAYMENTS_MANAGE,
                GymPermission::PAYMENTS_REVIEW,
                GymPermission::SUBSCRIPTIONS_VIEW,
                GymPermission::SUBSCRIPTIONS_MANAGE,
                GymPermission::MEMBERS_VIEW,
            ],
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