<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipPlanRequest;
use App\Http\Requests\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::PLANS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query(), $request),
            $request,
        );

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 10))
        );
    }

    public function store(StoreMembershipPlanRequest $request)
    {
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $plan = MembershipPlan::create($data);

        return response()->json([
            'message' => 'Membership plan created successfully',
            'data' => $plan,
        ], 201);
    }

    public function show(Request $request, MembershipPlan $membershipPlan)
    {
        $this->requirePermission($request, GymPermission::PLANS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query()->whereKey($membershipPlan->id), $request),
            $request,
        );

        return response()->json([
            'data' => $query->firstOrFail(),
        ]);
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

        return response()->json([
            'message' => 'Membership plan updated successfully',
            'data' => $plan->fresh(),
        ]);
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