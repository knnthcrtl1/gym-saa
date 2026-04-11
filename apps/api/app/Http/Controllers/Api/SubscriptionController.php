<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Subscription;
use App\Support\BelongsToTenant;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use BelongsToTenant;

    public function index(Request $request)
    {
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

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $subscription = Subscription::create($data);

        return response()->json([
            'message' => 'Subscription created successfully',
            'data' => $subscription->load(['member', 'membershipPlan']),
        ], 201);
    }

    public function show(Request $request, Subscription $subscription)
    {
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

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
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
}
