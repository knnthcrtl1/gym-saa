<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::BRANCHES_VIEW);

        $query = $this->scopeToTenant(Branch::query(), $request);

        return response()->json($query->latest()->paginate(10));
    }

    public function store(StoreBranchRequest $request)
    {
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $branch = Branch::create($data);

        return response()->json($branch, 201);
    }

    public function show(Request $request, Branch $branch)
    {
        $this->requirePermission($request, GymPermission::BRANCHES_VIEW);

        $query = $this->scopeToTenant(Branch::query()->whereKey($branch->id), $request);

        return response()->json($query->firstOrFail());
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $scopedBranch = $this->scopeToTenant(Branch::query()->whereKey($branch->id), $request)->firstOrFail();
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $scopedBranch->update($data);

        return response()->json($scopedBranch);
    }

    public function destroy(Request $request, Branch $branch)
    {
        $this->requirePermission($request, GymPermission::BRANCHES_MANAGE);

        $scopedBranch = $this->scopeToTenant(Branch::query()->whereKey($branch->id), $request)->firstOrFail();
        $scopedBranch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully',
        ]);
    }
}