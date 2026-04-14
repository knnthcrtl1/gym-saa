<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::SUBSCRIPTIONS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::with(['member', 'membershipPlan']), $request),
            $request,
        );

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 10))
        );
    }

    public function store(StoreSubscriptionRequest $request)
    {
        $data = $request->validated();

        unset($data['end_date']);

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $data['end_date'] = $this->computeSubscriptionEndDate($data, $request);

        $subscription = Subscription::create($data);

        return response()->json([
            'message' => 'Subscription created successfully',
            'data' => $subscription->load(['member', 'membershipPlan']),
        ], 201);
    }

    public function show(Request $request, Subscription $subscription)
    {
        $this->requirePermission($request, GymPermission::SUBSCRIPTIONS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::with(['member', 'membershipPlan'])->whereKey($subscription->id), $request),
            $request,
        );

        return response()->json([
            'data' => $query->firstOrFail(),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $scopedSubscription = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::query()->whereKey($subscription->id), $request),
            $request,
        )->firstOrFail();
        $data = $request->validated();

        unset($data['end_date']);

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        if (array_key_exists('membership_plan_id', $data) || array_key_exists('start_date', $data)) {
            $data['end_date'] = $this->computeSubscriptionEndDate($data, $request, $scopedSubscription);
        }

        $scopedSubscription->update($data);

        return response()->json([
            'message' => 'Subscription updated successfully',
            'data' => $scopedSubscription->fresh()->load(['member', 'membershipPlan']),
        ]);
    }

    public function destroy(Request $request, Subscription $subscription)
    {
        $scopedSubscription = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::query()->whereKey($subscription->id), $request),
            $request,
        )->firstOrFail();
        $scopedSubscription->delete();

        return response()->json([
            'message' => 'Subscription deleted successfully',
        ]);
    }

    private function computeSubscriptionEndDate(array $data, Request $request, ?Subscription $existing = null): string
    {
        $planId = $data['membership_plan_id'] ?? $existing?->membership_plan_id;
        $startDate = Carbon::parse($data['start_date'] ?? $existing?->start_date ?? now());
        $plan = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query()->whereKey($planId), $request),
            $request,
        )->firstOrFail();

        return match ($plan->duration_type) {
            'day' => $startDate->copy()->addDays($plan->duration_value)->toDateString(),
            'week' => $startDate->copy()->addWeeks($plan->duration_value)->toDateString(),
            'month' => $startDate->copy()->addMonths($plan->duration_value)->toDateString(),
            'year' => $startDate->copy()->addYears($plan->duration_value)->toDateString(),
            'session' => $startDate->copy()->addMonths(1)->toDateString(),
            default => $startDate->copy()->addMonths(1)->toDateString(),
        };
    }
}
