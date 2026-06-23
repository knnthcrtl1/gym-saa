<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\User;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::STAFF_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(
                User::query()
                    ->with(['branch', 'tenant'])
                    ->whereIn('role', ['gym_admin', 'staff']),
                $request,
            ),
            $request,
        );

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        if ($request->filled('staff_role')) {
            $query->where('staff_role', $request->string('staff_role')->toString());
        }

        $staff = $query->latest()->paginate($request->integer('per_page', 10));
        $staff->getCollection()->transform(fn (User $user) => $user->toStaffArray());

        return response()->json($staff);
    }

    public function store(StoreStaffRequest $request)
    {
        $data = $this->resolvePayload($request->validated(), $request->user());

        $staff = User::query()->create($data)->load(['branch', 'tenant']);

        return response()->json([
            'message' => 'Staff account created successfully',
            'data' => $staff->toStaffArray(),
        ], 201);
    }

    public function show(Request $request, User $staff)
    {
        $this->requirePermission($request, GymPermission::STAFF_VIEW);

        $resolvedStaff = $this->findScopedStaff($request, $staff);

        return response()->json([
            'data' => $resolvedStaff->toStaffArray(),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $staff)
    {
        $resolvedStaff = $this->findScopedStaff($request, $staff);
        $data = $this->resolvePayload($request->validated(), $request->user(), true);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $resolvedStaff->update($data);

        return response()->json([
            'message' => 'Staff account updated successfully',
            'data' => $resolvedStaff->fresh(['branch', 'tenant'])->toStaffArray(),
        ]);
    }

    public function destroy(Request $request, User $staff)
    {
        $this->requirePermission($request, GymPermission::STAFF_MANAGE);

        abort_if((int) $request->user()?->id === (int) $staff->id, 422, 'You cannot delete your own account.');

        $resolvedStaff = $this->findScopedStaff($request, $staff);
        $resolvedStaff->delete();

        return response()->json([
            'message' => 'Staff account deleted successfully',
        ]);
    }

    private function findScopedStaff(Request $request, User $staff): User
    {
        return $this->scopeToBranchIfStaff(
            $this->scopeToTenant(
                User::query()
                    ->with(['branch', 'tenant'])
                    ->whereIn('role', ['gym_admin', 'staff'])
                    ->whereKey($staff->id),
                $request,
            ),
            $request,
        )->firstOrFail();
    }

    private function resolvePayload(array $data, ?User $actor, bool $isUpdate = false): array
    {
        if ($actor && $actor->role !== 'super_admin') {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if (($data['role'] ?? null) === 'gym_admin') {
            $data['staff_role'] = $data['staff_role'] ?? 'owner';
        }

        if (($data['role'] ?? null) === 'staff') {
            $data['staff_role'] = $data['staff_role'] ?? 'front_desk';
        }

        if (array_key_exists('permissions', $data)) {
            $data['permissions'] = array_values(array_unique(array_filter($data['permissions'] ?? [], 'is_string')));
        } elseif (! $isUpdate) {
            $data['permissions'] = GymPermission::defaultFor($data['role'] ?? null, $data['staff_role'] ?? null);
        }

        return $data;
    }
}