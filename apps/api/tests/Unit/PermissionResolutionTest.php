<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\GymPermission;
use PHPUnit\Framework\TestCase;

class PermissionResolutionTest extends TestCase
{
    public function test_super_admin_gets_wildcard_permissions(): void
    {
        $permissions = GymPermission::defaultFor('super_admin');

        $this->assertSame(['*'], $permissions);
    }

    public function test_gym_admin_gets_default_gym_admin_permissions(): void
    {
        $permissions = GymPermission::defaultFor('gym_admin');

        $this->assertContains(GymPermission::DASHBOARD_VIEW, $permissions);
        $this->assertContains(GymPermission::MEMBERS_VIEW, $permissions);
        $this->assertContains(GymPermission::MEMBERS_MANAGE, $permissions);
        $this->assertContains(GymPermission::PLANS_VIEW, $permissions);
        $this->assertContains(GymPermission::PLANS_MANAGE, $permissions);
        $this->assertContains(GymPermission::SUBSCRIPTIONS_VIEW, $permissions);
        $this->assertContains(GymPermission::SUBSCRIPTIONS_MANAGE, $permissions);
        $this->assertContains(GymPermission::PAYMENTS_VIEW, $permissions);
        $this->assertContains(GymPermission::PAYMENTS_MANAGE, $permissions);
        $this->assertContains(GymPermission::PAYMENTS_REVIEW, $permissions);
        $this->assertContains(GymPermission::ATTENDANCE_VIEW, $permissions);
        $this->assertContains(GymPermission::ATTENDANCE_MANAGE, $permissions);
        $this->assertContains(GymPermission::STAFF_VIEW, $permissions);
        $this->assertContains(GymPermission::STAFF_MANAGE, $permissions);
        $this->assertContains(GymPermission::BRANCHES_VIEW, $permissions);
        $this->assertContains(GymPermission::BRANCHES_MANAGE, $permissions);
        $this->assertContains(GymPermission::AUDIT_LOGS_VIEW, $permissions);
        $this->assertNotContains(GymPermission::TENANTS_VIEW, $permissions);
        $this->assertNotContains(GymPermission::TENANTS_MANAGE, $permissions);
    }

    public function test_staff_owner_gets_same_permissions_as_gym_admin(): void
    {
        $ownerPermissions = GymPermission::defaultFor('staff', 'owner');
        $gymAdminPermissions = GymPermission::defaultFor('gym_admin');

        $this->assertSame($gymAdminPermissions, $ownerPermissions);
    }

    public function test_staff_manager_gets_manager_defaults(): void
    {
        $permissions = GymPermission::defaultFor('staff', 'manager');

        $this->assertContains(GymPermission::DASHBOARD_VIEW, $permissions);
        $this->assertContains(GymPermission::MEMBERS_VIEW, $permissions);
        $this->assertContains(GymPermission::MEMBERS_MANAGE, $permissions);
        $this->assertContains(GymPermission::PLANS_VIEW, $permissions);
        $this->assertContains(GymPermission::SUBSCRIPTIONS_VIEW, $permissions);
        $this->assertContains(GymPermission::SUBSCRIPTIONS_MANAGE, $permissions);
        $this->assertContains(GymPermission::PAYMENTS_VIEW, $permissions);
        $this->assertContains(GymPermission::PAYMENTS_MANAGE, $permissions);
        $this->assertContains(GymPermission::ATTENDANCE_VIEW, $permissions);
        $this->assertContains(GymPermission::ATTENDANCE_MANAGE, $permissions);
        $this->assertContains(GymPermission::STAFF_VIEW, $permissions);
        $this->assertNotContains(GymPermission::STAFF_MANAGE, $permissions);
        $this->assertNotContains(GymPermission::PLANS_MANAGE, $permissions);
        $this->assertNotContains(GymPermission::BRANCHES_VIEW, $permissions);
        $this->assertNotContains(GymPermission::BRANCHES_MANAGE, $permissions);
    }

    public function test_staff_front_desk_gets_front_desk_defaults(): void
    {
        $permissions = GymPermission::defaultFor('staff', 'front_desk');

        $this->assertContains(GymPermission::DASHBOARD_VIEW, $permissions);
        $this->assertContains(GymPermission::MEMBERS_VIEW, $permissions);
        $this->assertContains(GymPermission::MEMBERS_MANAGE, $permissions);
        $this->assertContains(GymPermission::PLANS_VIEW, $permissions);
        $this->assertContains(GymPermission::SUBSCRIPTIONS_VIEW, $permissions);
        $this->assertContains(GymPermission::SUBSCRIPTIONS_MANAGE, $permissions);
        $this->assertContains(GymPermission::PAYMENTS_VIEW, $permissions);
        $this->assertContains(GymPermission::PAYMENTS_MANAGE, $permissions);
        $this->assertContains(GymPermission::ATTENDANCE_VIEW, $permissions);
        $this->assertContains(GymPermission::ATTENDANCE_MANAGE, $permissions);
        $this->assertNotContains(GymPermission::STAFF_VIEW, $permissions);
        $this->assertNotContains(GymPermission::STAFF_MANAGE, $permissions);
    }

    public function test_staff_trainer_gets_limited_view_permissions(): void
    {
        $permissions = GymPermission::defaultFor('staff', 'trainer');

        $this->assertSame([
            GymPermission::MEMBERS_VIEW,
            GymPermission::SUBSCRIPTIONS_VIEW,
            GymPermission::ATTENDANCE_VIEW,
        ], $permissions);
    }

    public function test_custom_permissions_on_user_override_role_defaults(): void
    {
        $user = new User;
        $user->role = 'staff';
        $user->staff_role = 'trainer';
        $user->permissions = [
            GymPermission::MEMBERS_VIEW,
            GymPermission::PAYMENTS_VIEW,
            GymPermission::PAYMENTS_MANAGE,
        ];

        $effective = $user->effectivePermissions();

        $this->assertSame([
            GymPermission::MEMBERS_VIEW,
            GymPermission::PAYMENTS_VIEW,
            GymPermission::PAYMENTS_MANAGE,
        ], $effective);

        // Trainer defaults would not include PAYMENTS_MANAGE, but custom overrides it
        $trainerDefaults = GymPermission::defaultFor('staff', 'trainer');
        $this->assertNotContains(GymPermission::PAYMENTS_MANAGE, $trainerDefaults);
        $this->assertContains(GymPermission::PAYMENTS_MANAGE, $effective);
    }

    public function test_super_admin_user_always_gets_wildcard(): void
    {
        $user = new User;
        $user->role = 'super_admin';
        $user->permissions = [GymPermission::MEMBERS_VIEW];

        $this->assertSame(['*'], $user->effectivePermissions());
        $this->assertTrue($user->hasPermission(GymPermission::TENANTS_MANAGE));
    }

    public function test_unknown_role_returns_empty_permissions(): void
    {
        $permissions = GymPermission::defaultFor('unknown_role');

        $this->assertSame([], $permissions);
    }

    public function test_null_role_returns_empty_permissions(): void
    {
        $permissions = GymPermission::defaultFor(null);

        $this->assertSame([], $permissions);
    }

    public function test_user_without_permissions_falls_back_to_role_defaults(): void
    {
        $user = new User;
        $user->role = 'gym_admin';
        $user->permissions = null;

        $effective = $user->effectivePermissions();

        $this->assertSame(GymPermission::defaultFor('gym_admin'), $effective);
    }
}
