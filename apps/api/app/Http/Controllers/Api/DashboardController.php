<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Support\BelongsToTenant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use BelongsToTenant;

    public function index(Request $request)
    {
        $activeMembers = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query(), $request),
            $request,
        )->where('status', 'active')->count();

        $expiredSubscriptions = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::query(), $request),
            $request,
        )->where('status', 'expired')->count();

        $todayCheckins = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Checkin::query(), $request),
            $request,
        )->whereDate('checkin_time', today())->count();

        $monthlyRevenue = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::query(), $request),
            $request,
        )->where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        return response()->json([
            'stats' => [
                'active_members' => $activeMembers,
                'expired_subscriptions' => $expiredSubscriptions,
                'today_checkins' => $todayCheckins,
                'monthly_revenue' => $monthlyRevenue,
            ],
        ]);
    }
}