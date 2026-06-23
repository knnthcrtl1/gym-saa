<?php

namespace Tests\Feature;

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

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    // ── empty database returns empty lists ───────────────────────────

    public function test_empty_members_list_returns_empty_data(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_empty_subscriptions_list_returns_empty_data(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/subscriptions')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_empty_payments_list_returns_empty_data(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/payments')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ── non-existent ID returns 404 ──────────────────────────────────

    public function test_non_existent_member_returns_404(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/members/99999')
            ->assertNotFound();
    }

    public function test_non_existent_subscription_returns_404(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/subscriptions/99999')
            ->assertNotFound();
    }

    public function test_non_existent_membership_plan_returns_404(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/membership-plans/99999')
            ->assertNotFound();
    }

    // ── idempotency for member creation ──────────────────────────────

    public function test_member_creation_with_same_idempotency_key_does_not_duplicate(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $payload = [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-IDEM-001',
            'first_name' => 'Idem',
            'last_name' => 'Potent',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ];

        // First request should succeed
        $this->postJson('/api/v1/members', $payload)
            ->assertCreated();

        // Second request with same member_code should fail (unique per tenant)
        $this->postJson('/api/v1/members', $payload)
            ->assertStatus(422);

        $this->assertDatabaseCount('members', 1);
    }

    // ── expired membership — checkin rejected ────────────────────────

    public function test_checkin_rejected_for_expired_subscription(): void
    {
        [
            'tenant' => $tenant,
            'branch' => $branch,
            'actor' => $actor,
            'member' => $member,
            'subscription' => $subscription,
        ] = $this->createCheckinFixture(
            endDate: now()->subDay()->toDateString(),
            status: 'active',
        );

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/checkins', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'source' => 'manual',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Member has an expired plan.');
    }

    public function test_checkin_rejected_for_subscription_with_expired_status(): void
    {
        [
            'tenant' => $tenant,
            'branch' => $branch,
            'actor' => $actor,
            'member' => $member,
            'subscription' => $subscription,
        ] = $this->createCheckinFixture(
            endDate: now()->addMonth()->toDateString(),
            status: 'expired',
        );

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/checkins', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'source' => 'manual',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Member has an expired plan.');
    }

    // ── duplicate member email within same tenant ────────────────────

    public function test_duplicate_member_email_within_same_tenant_rejected(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-DUP-001',
            'first_name' => 'First',
            'last_name' => 'Member',
            'email' => 'duplicate@example.test',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/members', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-DUP-002',
            'first_name' => 'Second',
            'last_name' => 'Member',
            'email' => 'duplicate@example.test',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_same_email_allowed_across_different_tenants(): void
    {
        // Tenant A
        $tenantA = Tenant::query()->create([
            'name' => 'Gym A',
            'slug' => 'gym-a-' . uniqid(),
            'status' => 'active',
        ]);

        $branchA = Branch::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Branch A',
            'status' => 'active',
        ]);

        Member::query()->create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'member_code' => 'MBR-CROSS-001',
            'first_name' => 'Cross',
            'last_name' => 'Tenant',
            'email' => 'shared@example.test',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        // Tenant B
        $tenantB = Tenant::query()->create([
            'name' => 'Gym B',
            'slug' => 'gym-b-' . uniqid(),
            'status' => 'active',
        ]);

        $branchB = Branch::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Branch B',
            'status' => 'active',
        ]);

        $actorB = User::query()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'name' => 'Admin B',
            'email' => 'admin-b+' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'gym_admin',
            'staff_role' => 'owner',
            'status' => 'active',
            'permissions' => GymPermission::defaultFor('gym_admin'),
        ]);

        Sanctum::actingAs($actorB);

        $this->postJson('/api/v1/members', [
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'member_code' => 'MBR-CROSS-002',
            'first_name' => 'Cross',
            'last_name' => 'Tenant',
            'email' => 'shared@example.test',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ])->assertCreated();
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function createFixture(
        string $role = 'gym_admin',
        ?string $staffRole = 'owner',
        ?array $permissions = null,
    ): array {
        $tenant = Tenant::query()->create([
            'name' => 'Test Gym',
            'slug' => 'test-gym-' . uniqid(),
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
            'name' => 'Test User',
            'email' => 'user+' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => $role,
            'staff_role' => $staffRole,
            'status' => 'active',
            'permissions' => $permissions,
        ]);

        return compact('tenant', 'branch', 'actor');
    }

    private function createCheckinFixture(
        ?string $endDate = null,
        string $status = 'active',
        string $paymentStatus = 'paid',
    ): array {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture(
            role: 'staff',
            staffRole: 'front_desk',
            permissions: [
                GymPermission::DASHBOARD_VIEW,
                GymPermission::MEMBERS_VIEW,
                GymPermission::SUBSCRIPTIONS_VIEW,
                GymPermission::ATTENDANCE_VIEW,
                GymPermission::ATTENDANCE_MANAGE,
            ],
        );

        $member = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-CHK-001',
            'first_name' => 'Checkin',
            'last_name' => 'Test',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        $plan = MembershipPlan::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Monthly Plan',
            'duration_type' => 'month',
            'duration_value' => 1,
            'price' => 1200,
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => $endDate ?? now()->addMonth()->toDateString(),
            'amount' => 1200,
            'payment_status' => $paymentStatus,
            'status' => $status,
        ]);

        return compact('tenant', 'branch', 'actor', 'member', 'subscription');
    }
}
