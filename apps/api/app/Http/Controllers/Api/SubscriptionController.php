<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\IdempotencyKey;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Services\AuditLogService;
use App\Services\IdempotencyService;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly IdempotencyService $idempotencyService,
    ) {
    }

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::SUBSCRIPTIONS_VIEW);

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

        unset($data['end_date']);

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $data['end_date'] = $this->computeSubscriptionEndDate($data, $request);

        $claim = $this->idempotencyService->claim($request, 'subscriptions.create', $data);

        if ($claim['replayed']) {
            return $this->replaySubscriptionResponse($claim['record']);
        }

        try {
            $subscription = DB::transaction(function () use ($data, $request) {
                $subscription = Subscription::create($data);

                $this->auditLogService->record(
                    'subscription.created',
                    $subscription,
                    $request->user(),
                    null,
                    $this->subscriptionAuditState($subscription),
                    null,
                    "Created subscription #{$subscription->id}",
                );

                return $subscription;
            });

            $this->idempotencyService->complete($claim['record'], 201, Subscription::class, $subscription->id);

            return response()->json([
                'message' => 'Subscription created successfully',
                'data' => $subscription->load(['member', 'membershipPlan']),
            ], 201);
        } catch (\Throwable $exception) {
            $this->idempotencyService->forget($claim['record']);

            throw $exception;
        }
    }

    public function show(Request $request, Subscription $subscription)
    {
        $this->requirePermission($request, GymPermission::SUBSCRIPTIONS_VIEW);

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

        unset($data['end_date']);

        if ($request->user()?->role !== 'super_admin') {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($request->user()?->role === 'staff' && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        if (array_key_exists('membership_plan_id', $data) || array_key_exists('start_date', $data)) {
            $data['end_date'] = $this->computeSubscriptionEndDate($data, $request, $scopedSubscription);
        }

        $before = $this->subscriptionAuditState($scopedSubscription);

        DB::transaction(function () use ($scopedSubscription, $data, $request, $before) {
            $scopedSubscription->update($data);

            $this->auditLogService->record(
                'subscription.updated',
                $scopedSubscription,
                $request->user(),
                $before,
                $this->subscriptionAuditState($scopedSubscription->fresh()),
                null,
                "Updated subscription #{$scopedSubscription->id}",
            );
        });

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

        $before = $this->subscriptionAuditState($scopedSubscription);

        DB::transaction(function () use ($scopedSubscription, $request, $before) {
            $this->auditLogService->record(
                'subscription.deleted',
                $scopedSubscription,
                $request->user(),
                $before,
                null,
                null,
                "Deleted subscription #{$scopedSubscription->id}",
            );

            $scopedSubscription->delete();
        });

        return response()->json([
            'message' => 'Subscription deleted successfully',
        ]);
    }

    public function auditLogs(Request $request, Subscription $subscription)
    {
        $this->requirePermission($request, GymPermission::SUBSCRIPTIONS_VIEW);

        $scopedSubscription = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Subscription::query()->whereKey($subscription->id), $request),
            $request,
        )->firstOrFail();

        return response()->json(
            $scopedSubscription->auditLogs()->with('actor')->latest()->paginate($request->integer('per_page', 15))
        );
    }

    private function computeSubscriptionEndDate(array $data, Request $request, ?Subscription $existing = null): string
    {
        $planId = $data['membership_plan_id'] ?? $existing?->membership_plan_id;
        $startDate = Carbon::parse($data['start_date'] ?? $existing?->start_date ?? now());
        $plan = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(MembershipPlan::query()->whereKey($planId), $request),
            $request,
        )->firstOrFail();

        return match ($plan->duration_type) {
            'day' => $startDate->copy()->addDays($plan->duration_value)->toDateString(),
            'week' => $startDate->copy()->addWeeks($plan->duration_value)->toDateString(),
            'month' => $startDate->copy()->addMonths($plan->duration_value)->toDateString(),
            'year' => $startDate->copy()->addYears($plan->duration_value)->toDateString(),
            'session' => $startDate->copy()->addMonths(1)->toDateString(),
            default => $startDate->copy()->addMonths(1)->toDateString(),
        };
    }

    private function subscriptionAuditState(Subscription $subscription): array
    {
        return [
            'member_id' => $subscription->member_id,
            'membership_plan_id' => $subscription->membership_plan_id,
            'start_date' => $subscription->start_date?->toDateString(),
            'end_date' => $subscription->end_date?->toDateString(),
            'amount' => $subscription->amount,
            'sessions_remaining' => $subscription->sessions_remaining,
            'payment_status' => $subscription->payment_status,
            'status' => $subscription->status,
        ];
    }

    private function replaySubscriptionResponse(IdempotencyKey $record): \Illuminate\Http\JsonResponse
    {
        $subscription = Subscription::with(['member', 'membershipPlan'])
            ->findOrFail($record->resource_id);

        return response()->json([
            'message' => 'Subscription already created for this idempotency key.',
            'data' => $subscription,
        ]);
    }
}
