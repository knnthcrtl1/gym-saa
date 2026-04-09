<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        return response()->json(Tenant::latest()->paginate(10));
    }

    public function store(StoreTenantRequest $request)
    {
        $tenant = Tenant::create($request->validated());

        return response()->json($tenant, 201);
    }

    public function show(Request $request, Tenant $tenant)
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        return response()->json($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $tenant->update($request->validated());

        return response()->json($tenant);
    }

    public function destroy(Request $request, Tenant $tenant)
    {
        abort_unless($request->user()?->role === 'super_admin', 403);

        $tenant->delete();

        return response()->json([
            'message' => 'Tenant deleted successfully',
        ]);
    }
}