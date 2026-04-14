<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use App\Support\AuthorizesGymPermissions;
use App\Support\GymPermission;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    use AuthorizesGymPermissions;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::TENANTS_VIEW);

        return response()->json(Tenant::latest()->paginate(10));
    }

    public function store(StoreTenantRequest $request)
    {
        $tenant = Tenant::create($request->validated());

        return response()->json($tenant, 201);
    }

    public function show(Request $request, Tenant $tenant)
    {
        $this->requirePermission($request, GymPermission::TENANTS_VIEW);

        return response()->json($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $tenant->update($request->validated());

        return response()->json($tenant);
    }

    public function destroy(Request $request, Tenant $tenant)
    {
        $this->requirePermission($request, GymPermission::TENANTS_MANAGE);

        $tenant->delete();

        return response()->json([
            'message' => 'Tenant deleted successfully',
        ]);
    }
}