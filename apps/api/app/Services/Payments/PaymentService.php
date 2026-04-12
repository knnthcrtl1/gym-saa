<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentWebhook;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private readonly PayMongoService $payMongoService)
    {
    }

    public function createCheckoutPayment(array $data, ?User $actor): array
    {
        $resolved = $this->applyActorScope($data, $actor);

        $payment = Payment::create([
            'tenant_id' => $resolved['tenant_id'],
            'branch_id' => $resolved['branch_id'],
            'member_id' => $resolved['member_id'],
            'subscription_id' => $resolved['subscription_id'] ?? null,
            'gateway' => 'paymongo',
            'currency' => strtoupper($resolved['currency'] ?? 'PHP'),
            'payment_date' => now(),
            'amount' => $resolved['amount'],
            'payment_method' => 'online',
            'reference_no' => null,
            'notes' => $resolved['notes'] ?? null,
            'status' => 'pending',
            'recorded_by' => $actor?->id,
        ]);

        $payment->load(['member', 'subscription']);

        $checkout = $this->payMongoService->createCheckoutSession($payment);

        $payment->update([
            'gateway_checkout_session_id' => $checkout['id'],
            'gateway_reference' => $checkout['reference_number'],
            'checkout_url' => $checkout['checkout_url'],
            'gateway_metadata' => $checkout['metadata'],
            'raw_response' => $checkout['response'],
            'reference_no' => $checkout['reference_number'],
        ]);

        return [
            'payment' => $payment->fresh()->load(['member', 'subscription']),
            'checkout_url' => $checkout['checkout_url'],
        ];
    }

    public function recordManualPayment(array $data, ?User $actor): Payment
    {
        $resolved = $this->applyActorScope($data, $actor);

        $payment = Payment::create([
            'tenant_id' => $resolved['tenant_id'],
            'branch_id' => $resolved['branch_id'],
            'member_id' => $resolved['member_id'],
            'subscription_id' => $resolved['subscription_id'] ?? null,
            'gateway' => null,
            'currency' => strtoupper($resolved['currency'] ?? 'PHP'),
            'payment_date' => Carbon::parse($resolved['payment_date']),
            'paid_at' => ($resolved['status'] ?? 'paid') === 'paid' ? Carbon::parse($resolved['payment_date']) : null,
            'amount' => $resolved['amount'],
            'payment_method' => $resolved['payment_method'],
            'reference_no' => $resolved['reference_no'] ?? null,
            'notes' => $resolved['notes'] ?? null,
            'status' => $resolved['status'] ?? 'paid',
            'recorded_by' => $actor?->id,
        ]);

        $this->syncSubscriptionPaymentStatus($payment->subscription);

        return $payment->fresh()->load(['member', 'subscription', 'recorder']);
    }

    public function handlePayMongoWebhook(string $rawPayload, array $headers): array
    {
        $payload = json_decode($rawPayload, true);

        if (! is_array($payload)) {
            return ['accepted' => false, 'message' => 'Invalid JSON payload.'];
        }

        $signatureHeader = Arr::first($headers['paymongo-signature'] ?? []);
        $signatureVerified = $this->payMongoService->verifySignature($signatureHeader, $rawPayload);

        if (! $signatureVerified) {
            $this->storeWebhook($payload, $headers, false);

            return ['accepted' => false, 'message' => 'Invalid webhook signature.'];
        }

        $eventId = Arr::get($payload, 'data.id');
        $eventType = Arr::get($payload, 'data.attributes.type');
        $resource = Arr::get($payload, 'data.attributes.data', []);
        $payment = $this->resolvePaymentFromWebhook($payload, $resource);
        $webhook = $this->storeWebhook($payload, $headers, true, $payment?->id, $eventId, $eventType, $resource);

        if ($webhook->processed_at) {
            return ['accepted' => true, 'message' => 'Webhook already processed.'];
        }

        if ($payment) {
            if ($eventType === 'checkout_session.payment.paid' || $eventType === 'payment.paid') {
                $this->markPaymentPaid($payment, $payload, $resource);
            }

            if ($eventType === 'payment.failed') {
                $this->markPaymentFailed($payment, $payload, $resource);
            }
        }

        $webhook->forceFill([
            'payment_id' => $payment?->id,
            'processed_at' => now(),
        ])->save();

        return ['accepted' => true, 'message' => 'Webhook received.'];
    }

    public function syncSubscriptionPaymentStatus(?Subscription $subscription): void
    {
        if (! $subscription) {
            return;
        }

        $paidAmount = (float) $subscription->payments()
            ->where('status', 'paid')
            ->sum('amount');
        $subscriptionAmount = (float) $subscription->amount;

        $paymentStatus = match (true) {
            $paidAmount <= 0 => 'unpaid',
            $paidAmount + 0.00001 < $subscriptionAmount => 'partial',
            default => 'paid',
        };

        $updates = ['payment_status' => $paymentStatus];

        if ($paymentStatus === 'paid' && $subscription->status === 'pending') {
            $updates['status'] = 'active';
        }

        $subscription->update($updates);
    }

    private function applyActorScope(array $data, ?User $actor): array
    {
        if ($actor && $actor->role !== 'super_admin') {
            $data['tenant_id'] = $actor->tenant_id;
        }

        if ($actor && $actor->role === 'staff' && $actor->branch_id) {
            $data['branch_id'] = $actor->branch_id;
        }

        return $data;
    }

    private function resolvePaymentFromWebhook(array $payload, array $resource): ?Payment
    {
        $metadataPaymentId = Arr::get($resource, 'attributes.metadata.payment_id')
            ?? Arr::get($payload, 'data.attributes.metadata.payment_id');

        if ($metadataPaymentId) {
            return Payment::query()->find($metadataPaymentId);
        }

        $resourceId = Arr::get($resource, 'id');

        if ($resourceId) {
            $payment = Payment::query()
                ->where('gateway_checkout_session_id', $resourceId)
                ->orWhere('gateway_payment_id', $resourceId)
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        $reference = Arr::get($resource, 'attributes.reference_number')
            ?? Arr::get($resource, 'attributes.metadata.reference_number');

        if ($reference) {
            return Payment::query()->where('reference_no', $reference)->first();
        }

        return null;
    }

    private function storeWebhook(
        array $payload,
        array $headers,
        bool $signatureVerified,
        ?int $paymentId = null,
        ?string $eventId = null,
        ?string $eventType = null,
        array $resource = [],
    ): PaymentWebhook {
        if ($eventId) {
            return PaymentWebhook::query()->firstOrCreate(
                [
                    'provider' => 'paymongo',
                    'provider_event_id' => $eventId,
                ],
                [
                    'payment_id' => $paymentId,
                    'event_type' => $eventType,
                    'resource_type' => Arr::get($resource, 'type'),
                    'resource_id' => Arr::get($resource, 'id'),
                    'signature_verified' => $signatureVerified,
                    'headers' => $headers,
                    'payload' => $payload,
                ],
            );
        }

        return PaymentWebhook::create([
            'provider' => 'paymongo',
            'payment_id' => $paymentId,
            'provider_event_id' => 'generated-'.Str::uuid(),
            'event_type' => $eventType,
            'resource_type' => Arr::get($resource, 'type'),
            'resource_id' => Arr::get($resource, 'id'),
            'signature_verified' => $signatureVerified,
            'headers' => $headers,
            'payload' => $payload,
            'processed_at' => $signatureVerified ? now() : null,
        ]);
    }

    private function markPaymentPaid(Payment $payment, array $payload, array $resource): void
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'gateway_payment_id' => Arr::get($resource, 'attributes.payments.0.id')
                ?? Arr::get($resource, 'id')
                ?? $payment->gateway_payment_id,
            'gateway_reference' => Arr::get($resource, 'attributes.reference_number')
                ?? $payment->gateway_reference,
            'raw_response' => $payload,
            'checkout_url' => Arr::get($resource, 'attributes.checkout_url') ?? $payment->checkout_url,
        ]);

        $this->syncSubscriptionPaymentStatus($payment->subscription);
    }

    private function markPaymentFailed(Payment $payment, array $payload, array $resource): void
    {
        $payment->update([
            'status' => 'failed',
            'gateway_payment_id' => Arr::get($resource, 'id') ?? $payment->gateway_payment_id,
            'raw_response' => $payload,
        ]);

        $this->syncSubscriptionPaymentStatus($payment->subscription);
    }
}