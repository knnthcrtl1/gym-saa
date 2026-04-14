<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckinRequest;
use App\Models\Checkin;
use App\Models\Member;
use App\Models\Subscription;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckinController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::ATTENDANCE_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(
                Checkin::query()->with(['member', 'subscription.membershipPlan', 'verifier']),
                $request,
            ),
            $request,
        );

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('checkin_time', $request->date('date'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->whereHas('member', function ($builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->latest('checkin_time')->paginate($request->integer('per_page', 10))
        );
    }

    public function store(StoreCheckinRequest $request)
    {
        $data = $request->validated();

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $member = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query()->whereKey($data['member_id']), $request),
            $request,
        )->firstOrFail();

        if ($member->status !== 'active') {
            throw new HttpException(422, 'Only active members can check in.');
        }

        $this->expirePastSubscriptions($member);

        $subscription = $this->resolveSubscription($request, $member, $data['subscription_id'] ?? null);

        $alreadyCheckedInToday = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(
                Checkin::query()
                    ->where('member_id', $member->id)
                    ->whereDate('checkin_time', today()),
                $request,
            ),
            $request,
        )->exists();

        if ($alreadyCheckedInToday) {
            throw new HttpException(422, 'Member is already checked in for today.');
        }

        $checkin = Checkin::query()->create([
            'tenant_id' => $data['tenant_id'],
            'branch_id' => $data['branch_id'],
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'checkin_time' => now(),
            'source' => $data['source'] ?? 'manual',
            'status' => 'checked_in',
            'verified_by' => $request->user()?->id,
        ])->load(['member', 'subscription.membershipPlan', 'verifier']);

        return response()->json([
            'message' => 'Check-in recorded successfully',
            'data' => $checkin,
        ], 201);
    }

    private function expirePastSubscriptions(Member $member): void
    {
        $member->subscriptions()
            ->whereDate('end_date', '<', today())
            ->whereIn('status', ['pending', 'active'])
            ->update(['status' => 'expired']);
    }

    private function resolveSubscription(Request $request, Member $member, ?int $subscriptionId = null): Subscription
    {
        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(
                Subscription::query()
                    ->where('member_id', $member->id)
                    ->orderByDesc('end_date')
                    ->orderByDesc('id'),
                $request,
            ),
            $request,
        );

        $subscription = $subscriptionId
            ? (clone $query)->whereKey($subscriptionId)->firstOrFail()
            : (clone $query)->first();

        if (! $subscription) {
            throw new HttpException(422, 'Member has no subscription on file.');
        }

        if ($subscription->end_date?->isBefore(today()) || $subscription->status === 'expired') {
            $subscription->update(['status' => 'expired']);

            throw new HttpException(422, 'Member has an expired plan.');
        }

        if (in_array($subscription->status, ['frozen', 'cancelled'], true)) {
            throw new HttpException(422, 'Membership is not active for check-in.');
        }

        if ($subscription->payment_status !== 'paid') {
            throw new HttpException(422, 'Member has an unpaid balance.');
        }

        if ($subscription->status === 'pending') {
            $subscription->update(['status' => 'active']);
        }

        return $subscription->fresh();
    }
}