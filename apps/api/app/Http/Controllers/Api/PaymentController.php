<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewPaymentRequest;
use App\Http\Requests\StoreManualPaymentRequest;
use App\Http\Requests\StorePaymentIntentRequest;
use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\IdempotencyKey;
use App\Services\IdempotencyService;
use App\Services\Payments\PaymentService;
use App\Support\AuthorizesGymPermissions;
use App\Support\BelongsToTenant;
use App\Support\GymPermission;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentController extends Controller
{
    use AuthorizesGymPermissions;
    use BelongsToTenant;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly IdempotencyService $idempotencyService,
    )
    {
    }

    public function index(Request $request)
    {
        $this->requirePermission($request, GymPermission::PAYMENTS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::with(['member', 'subscription.membershipPlan', 'recorder', 'reviewer', 'proofs.uploader']), $request),
            $request,
        );

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->integer('subscription_id'));
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        return response()->json(
            $query->latest('payment_date')->paginate($request->integer('per_page', 10))
        );
    }

    public function createIntent(StorePaymentIntentRequest $request)
    {
        $data = $request->validated();
        $this->ensureScopedEntities($request, $data);

        $claim = $this->idempotencyService->claim($request, 'payments.intent', $data);

        if ($claim['replayed']) {
            return $this->replayPaymentResponse($claim['record'], true);
        }

        try {
            $result = $this->paymentService->createCheckoutPayment($data, $request->user());
            $this->idempotencyService->complete($claim['record'], 201, Payment::class, $result['payment']->id);

            return response()->json([
                'message' => 'Payment checkout created successfully',
                'data' => [
                    'payment' => $result['payment'],
                    'checkout_url' => $result['checkout_url'],
                ],
            ], 201);
        } catch (\Throwable $exception) {
            $this->idempotencyService->forget($claim['record']);

            throw $exception;
        }
    }

    public function storeManual(StoreManualPaymentRequest $request)
    {
        $data = $request->validated();
        $this->ensureScopedEntities($request, $data);

        $claim = $this->idempotencyService->claim(
            $request,
            'payments.manual',
            [
                ...$data,
                'proof' => $this->proofFingerprint($request),
            ],
        );

        if ($claim['replayed']) {
            return $this->replayPaymentResponse($claim['record'], false);
        }

        try {
            $payment = $this->paymentService->recordManualPayment(
                $data,
                $request->user(),
                $request->file('proof'),
            );

            $this->idempotencyService->complete($claim['record'], 201, Payment::class, $payment->id);

            return response()->json([
                'message' => 'Payment recorded successfully',
                'data' => $payment,
            ], 201);
        } catch (\Throwable $exception) {
            $this->idempotencyService->forget($claim['record']);

            throw $exception;
        }
    }

    public function uploadProof(UploadPaymentProofRequest $request, Payment $payment)
    {
        $resolvedPayment = $this->findScopedPayment($request, $payment);

        $updatedPayment = $this->paymentService->uploadManualProof(
            $resolvedPayment,
            $request->file('proof'),
            $request->user(),
        );

        return response()->json([
            'message' => 'Payment proof uploaded successfully',
            'data' => $updatedPayment,
        ]);
    }

    public function verify(ReviewPaymentRequest $request, Payment $payment)
    {
        $resolvedPayment = $this->findScopedPayment($request, $payment);
        $updatedPayment = $this->paymentService->verifyManualPayment(
            $resolvedPayment,
            $request->user(),
            $request->validated('notes'),
        );

        return response()->json([
            'message' => 'Payment verified successfully',
            'data' => $updatedPayment,
        ]);
    }

    public function reject(ReviewPaymentRequest $request, Payment $payment)
    {
        $resolvedPayment = $this->findScopedPayment($request, $payment);
        $updatedPayment = $this->paymentService->rejectManualPayment(
            $resolvedPayment,
            $request->user(),
            $request->validated('notes'),
        );

        return response()->json([
            'message' => 'Payment rejected successfully',
            'data' => $updatedPayment,
        ]);
    }

    public function show(Request $request, Payment $payment)
    {
        $this->requirePermission($request, GymPermission::PAYMENTS_VIEW);

        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::with(['member', 'subscription.membershipPlan', 'recorder', 'reviewer', 'proofs.uploader'])->whereKey($payment->id), $request),
            $request,
        );

        return response()->json([
            'data' => $query->firstOrFail(),
        ]);
    }

    public function auditLogs(Request $request, Payment $payment)
    {
        $this->requirePermission($request, GymPermission::PAYMENTS_VIEW);

        $resolvedPayment = $this->findScopedPayment($request, $payment);

        return response()->json(
            $resolvedPayment->auditLogs()->with('actor')->paginate($request->integer('per_page', 15))
        );
    }

    private function findScopedPayment(Request $request, Payment $payment): Payment
    {
        return $this->scopeToBranchIfStaff(
            $this->scopeToTenant(
                Payment::with(['member', 'subscription.membershipPlan', 'recorder', 'reviewer', 'proofs.uploader'])
                    ->whereKey($payment->id),
                $request,
            ),
            $request,
        )->firstOrFail();
    }

    private function ensureScopedEntities(Request $request, array $data): void
    {
        $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Member::query()->whereKey($data['member_id']), $request),
            $request,
        )->firstOrFail();

        if (! empty($data['subscription_id'])) {
            $subscription = $this->scopeToBranchIfStaff(
                $this->scopeToTenant(Subscription::query()->whereKey($data['subscription_id']), $request),
                $request,
            )->firstOrFail();

            if ((int) $subscription->member_id !== (int) $data['member_id']) {
                throw new HttpException(422, 'Selected subscription does not belong to the provided member.');
            }
        }
    }

    private function replayPaymentResponse(IdempotencyKey $record, bool $isCheckout): \Illuminate\Http\JsonResponse
    {
        $payment = Payment::with(['member', 'subscription.membershipPlan', 'recorder', 'reviewer', 'proofs.uploader'])
            ->findOrFail($record->resource_id);

        if ($isCheckout) {
            return response()->json([
                'message' => 'Payment checkout already created for this idempotency key.',
                'data' => [
                    'payment' => $payment,
                    'checkout_url' => $payment->checkout_url,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Payment already recorded for this idempotency key.',
            'data' => $payment,
        ]);
    }

    private function proofFingerprint(Request $request): ?array
    {
        $proof = $request->file('proof');

        if (! $proof) {
            return null;
        }

        return [
            'name' => $proof->getClientOriginalName(),
            'size' => $proof->getSize(),
            'mime_type' => $proof->getClientMimeType(),
        ];
    }
}
