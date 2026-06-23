<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Checkin;
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

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    // ── members ──────────────────────────────────────────────────────

    public function test_gym_admin_cannot_see_other_tenants_members(): void
    {
        ['actorA' => $actorA, 'memberB' => $memberB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $response = $this->getJson('/api/v1/members');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($memberB->id, $ids);
    }

    public function test_gym_admin_cannot_view_other_tenants_member(): void
    {
        ['actorA' => $actorA, 'memberB' => $memberB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $this->getJson("/api/v1/members/{$memberB->id}")
            ->assertNotFound();
    }

    public function test_gym_admin_cannot_update_other_tenants_member(): void
    {
        ['actorA' => $actorA, 'memberB' => $memberB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $this->putJson("/api/v1/members/{$memberB->id}", [
            'first_name' => 'Hacked',
        ])->assertNotFound();
    }

    public function test_gym_admin_cannot_delete_other_tenants_member(): void
    {
        ['actorA' => $actorA, 'memberB' => $memberB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $this->deleteJson("/api/v1/members/{$memberB->id}")
            ->assertNotFound();
    }

    // ── subscriptions ────────────────────────────────────────────────

    public function test_gym_admin_cannot_see_other_tenants_subscriptions(): void
    {
        ['actorA' => $actorA, 'subscriptionB' => $subscriptionB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $response = $this->getJson('/api/v1/subscriptions');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($subscriptionB->id, $ids);
    }

    public function test_gym_admin_cannot_view_other_tenants_subscription(): void
    {
        ['actorA' => $actorA, 'subscriptionB' => $subscriptionB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $this->getJson("/api/v1/subscriptions/{$subscriptionB->id}")
            ->assertNotFound();
    }

    public function test_gym_admin_cannot_delete_other_tenants_subscription(): void
    {
        ['actorA' => $actorA, 'subscriptionB' => $subscriptionB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $this->deleteJson("/api/v1/subscriptions/{$subscriptionB->id}")
            ->assertNotFound();
    }

    // ── payments ─────────────────────────────────────────────────────

    public function test_gym_admin_cannot_see_other_tenants_payments(): void
    {
        ['actorA' => $actorA, 'paymentB' => $paymentB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $response = $this->getJson('/api/v1/payments');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($paymentB->id, $ids);
    }

    public function test_gym_admin_cannot_view_other_tenants_payment(): void
    {
        ['actorA' => $actorA, 'paymentB' => $paymentB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $this->getJson("/api/v1/payments/{$paymentB->id}")
            ->assertNotFound();
    }

    // ── checkins ─────────────────────────────────────────────────────

    public function test_gym_admin_cannot_see_other_tenants_checkins(): void
    {
        ['actorA' => $actorA, 'checkinB' => $checkinB] = $this->createTwoTenantFixture();

        Sanctum::actingAs($actorA);

        $response = $this->getJson('/api/v1/checkins');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($checkinB->id, $ids);
    }

    // ── super_admin CAN see all tenants ──────────────────────────────

    public function test_super_admin_can_see_members_across_tenants(): void
    {
        ['memberA' => $memberA, 'memberB' => $memberB] = $this->createTwoTenantFixture();

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

        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/v1/members');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($memberA->id, $ids);
        $this->assertContains($memberB->id, $ids);
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function createTwoTenantFixture(): array
    {
        // Tenant A
        $tenantA = Tenant::query()->create([
            'name' => 'Gym Alpha',
            'slug' => 'gym-alpha-' . uniqid(),
            'status' => 'active',
        ]);

        $branchA = Branch::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Alpha Branch',
            'status' => 'active',
        ]);

        $actorA = User::query()->create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'name' => 'Admin Alpha',
            'email' => 'admin-a+' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'gym_admin',
            'staff_role' => 'owner',
            'status' => 'active',
            'permissions' => GymPermission::defaultFor('gym_admin'),
        ]);

        $memberA = Member::query()->create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'member_code' => 'MBR-A001',
            'first_name' => 'Alpha',
            'last_name' => 'Member',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        $planA = MembershipPlan::query()->create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'name' => 'Monthly Alpha',
            'duration_type' => 'month',
            'duration_value' => 1,
            'price' => 1000,
            'status' => 'active',
        ]);

        $subscriptionA = Subscription::query()->create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'member_id' => $memberA->id,
            'membership_plan_id' => $planA->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'amount' => 1000,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        // Tenant B
        $tenantB = Tenant::query()->create([
            'name' => 'Gym Beta',
            'slug' => 'gym-beta-' . uniqid(),
            'status' => 'active',
        ]);

        $branchB = Branch::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Beta Branch',
            'status' => 'active',
        ]);

        $actorB = User::query()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'name' => 'Admin Beta',
            'email' => 'admin-b+' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'gym_admin',
            'staff_role' => 'owner',
            'status' => 'active',
            'permissions' => GymPermission::defaultFor('gym_admin'),
        ]);

        $memberB = Member::query()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'member_code' => 'MBR-B001',
            'first_name' => 'Beta',
            'last_name' => 'Member',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        $planB = MembershipPlan::query()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'name' => 'Monthly Beta',
            'duration_type' => 'month',
            'duration_value' => 1,
            'price' => 1500,
            'status' => 'active',
        ]);

        $subscriptionB = Subscription::query()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'member_id' => $memberB->id,
            'membership_plan_id' => $planB->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'amount' => 1500,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        $paymentB = Payment::query()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'member_id' => $memberB->id,
            'subscription_id' => $subscriptionB->id,
            'gateway' => 'manual',
            'currency' => 'PHP',
            'payment_date' => now(),
            'amount' => 1500,
            'payment_method' => 'cash',
            'status' => 'paid',
            'recorded_by' => $actorB->id,
        ]);

        $checkinB = Checkin::query()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'member_id' => $memberB->id,
            'subscription_id' => $subscriptionB->id,
            'checkin_time' => now(),
            'source' => 'manual',
            'status' => 'checked_in',
            'verified_by' => $actorB->id,
        ]);

        return compact(
            'tenantA', 'branchA', 'actorA', 'memberA', 'planA', 'subscriptionA',
            'tenantB', 'branchB', 'actorB', 'memberB', 'planB', 'subscriptionB',
            'paymentB', 'checkinB',
        );
    }
}
