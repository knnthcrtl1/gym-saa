<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GymPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_subscription_is_idempotent_with_same_key(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'plan' => $plan, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $payload = [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ];

        $headers = ['X-Idempotency-Key' => 'sub-create-001'];

        $first = $this->postJson('/api/v1/subscriptions', $payload, $headers)
            ->assertCreated();

        $second = $this->postJson('/api/v1/subscriptions', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Subscription already created for this idempotency key.');

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );

        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_reusing_idempotency_key_with_different_payload_returns_conflict(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'plan' => $plan, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $headers = ['X-Idempotency-Key' => 'sub-create-002'];

        $this->postJson('/api/v1/subscriptions', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/subscriptions', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'amount' => 2000,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ], $headers)
            ->assertStatus(409);
    }

    public function test_subscription_create_records_audit_log(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'plan' => $plan, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/subscriptions', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription.created',
            'auditable_type' => Subscription::class,
            'actor_id' => $actor->id,
        ]);
    }

    public function test_subscription_update_records_audit_log_with_before_after(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'plan' => $plan, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

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

        $this->putJson("/api/v1/subscriptions/{$subscription->id}", [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'amount' => 2000,
            'payment_status' => 'paid',
            'status' => 'active',
        ])->assertOk();

        $auditLog = AuditLog::query()
            ->where('action', 'subscription.updated')
            ->where('auditable_type', Subscription::class)
            ->where('auditable_id', $subscription->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertSame('unpaid', $auditLog->before_state['payment_status']);
        $this->assertSame('paid', $auditLog->after_state['payment_status']);
        $this->assertContains('payment_status', $auditLog->changed_fields);
    }

    public function test_subscription_delete_records_audit_log(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'plan' => $plan, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

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

        $this->deleteJson("/api/v1/subscriptions/{$subscription->id}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription.deleted',
            'auditable_type' => Subscription::class,
            'auditable_id' => $subscription->id,
            'actor_id' => $actor->id,
        ]);
    }

    public function test_subscription_audit_logs_endpoint(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'member' => $member, 'plan' => $plan, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $subscription = $this->postJson('/api/v1/subscriptions', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'amount' => 1500,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ])->assertCreated()->json('data');

        $this->getJson('/api/v1/subscriptions/'.$subscription['id'].'/audit-logs')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'subscription.created')
            ->assertJsonPath('data.0.actor_id', $actor->id);
    }

    private function createFixture(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Fitness',
            'slug' => 'acme-fitness-'.uniqid(),
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
            'permissions' => GymPermission::defaultFor('gym_admin'),
        ]);

        $member = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
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

        return compact('tenant', 'branch', 'actor', 'member', 'plan');
    }
}
