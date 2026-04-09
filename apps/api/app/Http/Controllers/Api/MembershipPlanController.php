<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipPlanRequest;
use App\Http\Requests\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use App\Support\BelongsToTenant;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    use BelongsToTenant;

    public function index(Request $request)
    {
        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query(), $request),
            $request,
        );

        return response()->json($query->latest()->paginate(10));
    }

    public function store(StoreMembershipPlanRequest $request)
    {
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $plan = MembershipPlan::create($data);

        return response()->json($plan, 201);
    }

    public function show(Request $request, MembershipPlan $membershipPlan)
    {
        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query()->whereKey($membershipPlan->id), $request),
            $request,
        );

        return response()->json($query->firstOrFail());
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membershipPlan)
    {
        $plan = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query()->whereKey($membershipPlan->id), $request),
            $request,
        )->firstOrFail();
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $plan->update($data);

        return response()->json($plan);
    }

    public function destroy(Request $request, MembershipPlan $membershipPlan)
    {
        $plan = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query()->whereKey($membershipPlan->id), $request),
            $request,
        )->firstOrFail();
        $plan->delete();

        return response()->json([
            'message' => 'Membership plan deleted successfully',
        ]);
    }
}