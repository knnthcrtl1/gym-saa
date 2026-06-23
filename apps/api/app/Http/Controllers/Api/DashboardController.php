<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::DASHBOARD_VIEW);

        $activeMembers = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query(), $request),
            $request,
        )->where('status', 'active')->count();

        $expiredMembers = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::query(), $request),
            $request,
        )->where(function ($query) {
            $query->where('status', 'expired')
                ->orWhereDate('end_date', '<', today());
        })->distinct('member_id')->count('member_id');

        $expiredSubscriptions = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::query(), $request),
            $request,
        )->where(function ($query) {
            $query->where('status', 'expired')
                ->orWhereDate('end_date', '<', today());
        })->count();

        $newMembersThisMonth = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query(), $request),
            $request,
        )->whereBetween('joined_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->count();

        $todayCheckins = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Checkin::query(), $request),
            $request,
        )->whereDate('checkin_time', today())->count();

        $paymentsToday = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::query(), $request),
            $request,
        )->where('status', 'paid')
            ->whereDate('payment_date', today())
            ->count();

        $paymentsThisMonth = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::query(), $request),
            $request,
        )->where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->count();

        $incomeToday = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::query(), $request),
            $request,
        )->where('status', 'paid')
            ->whereDate('payment_date', today())
            ->sum('amount');

        $monthlyRevenue = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::query(), $request),
            $request,
        )->where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $upcomingRenewals = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::query(), $request),
            $request,
        )->whereDate('end_date', '>=', today())
            ->whereDate('end_date', '<=', today()->copy()->addDays(7))
            ->whereIn('status', ['pending', 'active'])
            ->count();

        return response()->json([
            'stats' => [
                'active_members' => $activeMembers,
                'expired_members' => $expiredMembers,
                'expired_subscriptions' => $expiredSubscriptions,
                'new_members_this_month' => $newMembersThisMonth,
                'today_checkins' => $todayCheckins,
                'payments_today' => $paymentsToday,
                'payments_this_month' => $paymentsThisMonth,
                'income_today' => $incomeToday,
                'monthly_revenue' => $monthlyRevenue,
                'upcoming_renewals' => $upcomingRenewals,
            ],
        ]);
    }
}