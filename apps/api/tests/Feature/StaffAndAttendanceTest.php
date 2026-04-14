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

class StaffAndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gym_admin_can_create_staff_account(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createOperatorFixture();

        Sanctum::actingAs($actor);

        $response = $this->postJson('/api/v1/staff', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Front Desk 1',
            'email' => 'frontdesk@example.test',
            'password' => 'password123',
            'role' => 'staff',
            'staff_role' => 'front_desk',
            'status' => 'active',
            'permissions' => [
                GymPermission::DASHBOARD_VIEW,
                GymPermission::MEMBERS_VIEW,
                GymPermission::ATTENDANCE_VIEW,
                GymPermission::ATTENDANCE_MANAGE,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'frontdesk@example.test')
            ->assertJsonPath('data.staff_role', 'front_desk');

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'email' => 'frontdesk@example.test',
            'role' => 'staff',
            'staff_role' => 'front_desk',
            'status' => 'active',
        ]);
    }

    public function test_front_desk_without_staff_permission_cannot_list_staff(): void
    {
        ['actor' => $actor] = $this->createOperatorFixture(
            role: 'staff',
            staffRole: 'front_desk',
            permissions: [
                GymPermission::DASHBOARD_VIEW,
                GymPermission::MEMBERS_VIEW,
                GymPermission::ATTENDANCE_VIEW,
                GymPermission::ATTENDANCE_MANAGE,
            ],
        );

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/staff')->assertForbidden();
    }

    public function test_checkin_can_be_recorded_once_per_day_for_paid_active_subscription(): void
    {
        [
            'tenant' => $tenant,
            'branch' => $branch,
            'actor' => $actor,
            'member' => $member,
            'subscription' => $subscription,
        ] = $this->createAttendanceFixture();

        Sanctum::actingAs($actor);

        $payload = [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'source' => 'manual',
        ];

        $this->postJson('/api/v1/checkins', $payload)
            ->assertCreated()
            ->assertJsonPath('data.member_id', $member->id)
            ->assertJsonPath('data.subscription_id', $subscription->id);

        $this->postJson('/api/v1/checkins', $payload)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Member is already checked in for today.');
    }

    public function test_checkin_is_blocked_for_unpaid_subscription(): void
    {
        [
            'tenant' => $tenant,
            'branch' => $branch,
            'actor' => $actor,
            'member' => $member,
            'subscription' => $subscription,
        ] = $this->createAttendanceFixture(paymentStatus: 'unpaid', status: 'pending');

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/checkins', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'source' => 'manual',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Member has an unpaid balance.');
    }

    private function createOperatorFixture(
        string $role = 'gym_admin',
        ?string $staffRole = 'owner',
        ?array $permissions = null,
    ): array {
        $tenant = Tenant::query()->create([
            'name' => 'Gym Alpha',
            'slug' => 'gym-alpha',
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
            'name' => 'Ops Admin',
            'email' => uniqid('ops-', true).'@example.test',
            'password' => 'password123',
            'role' => $role,
            'staff_role' => $staffRole,
            'status' => 'active',
            'permissions' => $permissions,
        ]);

        return compact('tenant', 'branch', 'actor');
    }

    private function createAttendanceFixture(
        string $paymentStatus = 'paid',
        string $status = 'active',
    ): array {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createOperatorFixture(
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
            'member_code' => 'MBR-2001',
            'first_name' => 'Alex',
            'last_name' => 'Tan',
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
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'amount' => 1200,
            'payment_status' => $paymentStatus,
            'status' => $status,
        ]);

        return compact('tenant', 'branch', 'actor', 'member', 'subscription');
    }
}