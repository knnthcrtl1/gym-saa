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
use App\Services\Payments\PaymentService;
use App\Support\BelongsToTenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentController extends Controller
{
    use BelongsToTenant;

    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function index(Request $request)
    {
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

        $result = $this->paymentService->createCheckoutPayment($data, $request->user());

        return response()->json([
            'message' => 'Payment checkout created successfully',
            'data' => [
                'payment' => $result['payment'],
                'checkout_url' => $result['checkout_url'],
            ],
        ], 201);
    }

    public function storeManual(StoreManualPaymentRequest $request)
    {
        $data = $request->validated();
        $this->ensureScopedEntities($request, $data);

        $payment = $this->paymentService->recordManualPayment(
            $data,
            $request->user(),
            $request->file('proof'),
        );

        return response()->json([
            'message' => 'Payment recorded successfully',
            'data' => $payment,
        ], 201);
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
        $query = $this->scopeToBranchIfStaff(
            $this->scopeToTenant(Payment::with(['member', 'subscription.membershipPlan', 'recorder', 'reviewer', 'proofs.uploader'])->whereKey($payment->id), $request),
            $request,
        );

        return response()->json([
            'data' => $query->firstOrFail(),
        ]);
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
}
