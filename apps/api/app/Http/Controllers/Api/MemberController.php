<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteMembersRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    private const SORTABLE_COLUMNS = [
        'created_at' => 'created_at',
        'joined_at' => 'joined_at',
        'member_code' => 'member_code',
        'status' => 'status',
    ];

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::MEMBERS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query(), $request),
            $request,
        );

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $sortBy = $request->string('sort_by')->toString();

        if ($sortBy === 'name') {
            $query->orderBy('first_name', $direction)->orderBy('last_name', $direction);
        } elseif (array_key_exists($sortBy, self::SORTABLE_COLUMNS)) {
            $query->orderBy(self::SORTABLE_COLUMNS[$sortBy], $direction);
        } else {
            $query->latest();
        }

        return response()->json(
            $query->paginate($perPage)->withQueryString(),
        );
    }

    public function store(StoreMemberRequest $request)
    {
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $member = Member::create($data);

        return response()->json([
            'message' => 'Member created successfully',
            'data' => $member,
        ], 201);
    }

    public function show(Request $request, Member $member)
    {
        $this->requirePermission($request, GymPermission::MEMBERS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query()->whereKey($member->id), $request),
            $request,
        );

        return response()->json([
            'data' => $query->firstOrFail(),
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        $scopedMember = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query()->whereKey($member->id), $request),
            $request,
        )->firstOrFail();
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $scopedMember->update($data);

        return response()->json([
            'message' => 'Member updated successfully',
            'data' => $scopedMember->fresh(),
        ]);
    }

    public function destroy(Request $request, Member $member)
    {
        $scopedMember = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query()->whereKey($member->id), $request),
            $request,
        )->firstOrFail();
        $scopedMember->delete();

        return response()->json([
            'message' => 'Member deleted successfully',
        ]);
    }

    public function bulkDestroy(BulkDeleteMembersRequest $request)
    {
        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query(), $request),
            $request,
        )->whereIn('id', $request->validated('ids'));

        $deleted = $query->delete();

        return response()->json([
            'message' => $deleted === 1
                ? '1 member deleted successfully'
                : "{$deleted} members deleted successfully",
            'deleted' => $deleted,
        ]);
    }
}