<?php

namespace App\Support;

class GymPermission
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const MEMBERS_VIEW = 'members.view';

    public const MEMBERS_MANAGE = 'members.manage';

    public const PLANS_VIEW = 'plans.view';

    public const PLANS_MANAGE = 'plans.manage';

    public const SUBSCRIPTIONS_VIEW = 'subscriptions.view';

    public const SUBSCRIPTIONS_MANAGE = 'subscriptions.manage';

    public const PAYMENTS_VIEW = 'payments.view';

    public const PAYMENTS_MANAGE = 'payments.manage';

    public const PAYMENTS_REVIEW = 'payments.review';

    public const ATTENDANCE_VIEW = 'attendance.view';

    public const ATTENDANCE_MANAGE = 'attendance.manage';

    public const STAFF_VIEW = 'staff.view';

    public const STAFF_MANAGE = 'staff.manage';

    public const BRANCHES_VIEW = 'branches.view';

    public const BRANCHES_MANAGE = 'branches.manage';

    public const TENANTS_VIEW = 'tenants.view';

    public const TENANTS_MANAGE = 'tenants.manage';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::MEMBERS_VIEW,
            self::MEMBERS_MANAGE,
            self::PLANS_VIEW,
            self::PLANS_MANAGE,
            self::SUBSCRIPTIONS_VIEW,
            self::SUBSCRIPTIONS_MANAGE,
            self::PAYMENTS_VIEW,
            self::PAYMENTS_MANAGE,
            self::PAYMENTS_REVIEW,
            self::ATTENDANCE_VIEW,
            self::ATTENDANCE_MANAGE,
            self::STAFF_VIEW,
            self::STAFF_MANAGE,
            self::BRANCHES_VIEW,
            self::BRANCHES_MANAGE,
            self::TENANTS_VIEW,
            self::TENANTS_MANAGE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultFor(?string $role, ?string $staffRole = null): array
    {
        if ($role === 'super_admin') {
            return ['*'];
        }

        return match ($role) {
            'gym_admin' => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::MEMBERS_MANAGE,
                self::PLANS_VIEW,
                self::PLANS_MANAGE,
                self::SUBSCRIPTIONS_VIEW,
                self::SUBSCRIPTIONS_MANAGE,
                self::PAYMENTS_VIEW,
                self::PAYMENTS_MANAGE,
                self::PAYMENTS_REVIEW,
                self::ATTENDANCE_VIEW,
                self::ATTENDANCE_MANAGE,
                self::STAFF_VIEW,
                self::STAFF_MANAGE,
                self::BRANCHES_VIEW,
                self::BRANCHES_MANAGE,
            ],
            'staff' => self::defaultForStaffRole($staffRole),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function defaultForStaffRole(?string $staffRole): array
    {
        return match ($staffRole) {
            'owner' => self::defaultFor('gym_admin'),
            'manager' => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::MEMBERS_MANAGE,
                self::PLANS_VIEW,
                self::PLANS_MANAGE,
                self::SUBSCRIPTIONS_VIEW,
                self::SUBSCRIPTIONS_MANAGE,
                self::PAYMENTS_VIEW,
                self::PAYMENTS_MANAGE,
                self::PAYMENTS_REVIEW,
                self::ATTENDANCE_VIEW,
                self::ATTENDANCE_MANAGE,
                self::STAFF_VIEW,
                self::BRANCHES_VIEW,
            ],
            'trainer' => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::SUBSCRIPTIONS_VIEW,
                self::ATTENDANCE_VIEW,
                self::ATTENDANCE_MANAGE,
            ],
            default => [
                self::DASHBOARD_VIEW,
                self::MEMBERS_VIEW,
                self::MEMBERS_MANAGE,
                self::PLANS_VIEW,
                self::SUBSCRIPTIONS_VIEW,
                self::SUBSCRIPTIONS_MANAGE,
                self::PAYMENTS_VIEW,
                self::PAYMENTS_MANAGE,
                self::ATTENDANCE_VIEW,
                self::ATTENDANCE_MANAGE,
            ],
        };
    }
}