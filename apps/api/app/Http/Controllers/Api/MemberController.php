<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Support\BelongsToTenant;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use BelongsToTenant;

    public function index(Request $request)
    {
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

        return response()->json($query->latest()->paginate(10));
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

        return response()->json($member, 201);
    }

    public function show(Request $request, Member $member)
    {
        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query()->whereKey($member->id), $request),
            $request,
        );

        return response()->json($query->firstOrFail());
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

        return response()->json($scopedMember);
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
}