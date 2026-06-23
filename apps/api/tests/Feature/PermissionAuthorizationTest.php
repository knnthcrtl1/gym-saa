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

class PermissionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ── gym_admin can manage members ─────────────────────────────────

    public function test_gym_admin_can_list_members(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-0001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_gym_admin_can_create_member(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/members', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-0002',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ])->assertCreated();
    }

    // ── staff (front_desk) can view members but cannot manage plans ──

    public function test_front_desk_can_view_members(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture(
            role: 'staff',
            staffRole: 'front_desk',
            permissions: GymPermission::defaultForStaffRole('front_desk'),
        );

        Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-0003',
            'first_name' => 'Alice',
            'last_name' => 'Lee',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/members')
            ->assertOk();
    }

    public function test_front_desk_cannot_manage_plans(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture(
            role: 'staff',
            staffRole: 'front_desk',
            permissions: GymPermission::defaultForStaffRole('front_desk'),
        );

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/membership-plans', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Illegal Plan',
            'duration_type' => 'month',
            'duration_value' => 1,
            'price' => 999,
            'status' => 'active',
        ])->assertForbidden();
    }

    // ── trainer cannot access payments ───────────────────────────────

    public function test_trainer_cannot_view_payments(): void
    {
        ['actor' => $actor] = $this->createFixture(
            role: 'staff',
            staffRole: 'trainer',
            permissions: GymPermission::defaultForStaffRole('trainer'),
        );

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/payments')
            ->assertForbidden();
    }

    public function test_trainer_cannot_view_dashboard(): void
    {
        ['actor' => $actor] = $this->createFixture(
            role: 'staff',
            staffRole: 'trainer',
            permissions: GymPermission::defaultForStaffRole('trainer'),
        );

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/dashboard')
            ->assertForbidden();
    }

    // ── super_admin can access all tenants' data ─────────────────────

    public function test_super_admin_can_view_all_tenants(): void
    {
        $superAdmin = User::query()->create([
            'tenant_id' => null,
            'branch_id' => null,
            'name' => 'Super Admin',
            'email' => 'super+' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'super_admin',
            'staff_role' => null,
            'status' => 'active',
        ]);

        Tenant::query()->create(['name' => 'Gym A', 'slug' => 'gym-a-' . uniqid(), 'status' => 'active']);
        Tenant::query()->create(['name' => 'Gym B', 'slug' => 'gym-b-' . uniqid(), 'status' => 'active']);

        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/v1/tenants')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ── gym_admin is limited to their tenant ─────────────────────────

    public function test_gym_admin_cannot_view_tenants(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/tenants')
            ->assertForbidden();
    }

    // ── custom permissions override defaults ─────────────────────────

    public function test_staff_with_custom_permissions_can_access_granted_routes(): void
    {
        ['actor' => $actor] = $this->createFixture(
            role: 'staff',
            staffRole: 'trainer',
            permissions: [
                GymPermission::MEMBERS_VIEW,
                GymPermission::PAYMENTS_VIEW,
            ],
        );

        Sanctum::actingAs($actor);

        // Trainer normally cannot view payments, but custom permissions allow it
        $this->getJson('/api/v1/payments')
            ->assertOk();
    }

    public function test_staff_with_custom_permissions_cannot_access_ungranted_routes(): void
    {
        ['actor' => $actor] = $this->createFixture(
            role: 'staff',
            staffRole: 'front_desk',
            permissions: [
                GymPermission::MEMBERS_VIEW,
            ],
        );

        Sanctum::actingAs($actor);

        // Front desk normally can view payments, but custom permissions restrict it
        $this->getJson('/api/v1/payments')
            ->assertForbidden();
    }

    // ── unauthenticated users get 401 ────────────────────────────────

    public function test_unauthenticated_user_gets_401_on_members(): void
    {
        $this->getJson('/api/v1/members')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_gets_401_on_subscriptions(): void
    {
        $this->getJson('/api/v1/subscriptions')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_gets_401_on_payments(): void
    {
        $this->getJson('/api/v1/payments')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_gets_401_on_dashboard(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_gets_401_on_staff(): void
    {
        $this->getJson('/api/v1/staff')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_gets_401_on_checkins(): void
    {
        $this->getJson('/api/v1/checkins')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_gets_401_on_branches(): void
    {
        $this->getJson('/api/v1/branches')
            ->assertUnauthorized();
    }

    public function test_unauthenticated_user_gets_401_on_membership_plans(): void
    {
        $this->getJson('/api/v1/membership-plans')
            ->assertUnauthorized();
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
}
