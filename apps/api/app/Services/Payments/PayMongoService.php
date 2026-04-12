<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService
{
    public function createCheckoutSession(Payment $payment): array
    {
        $secretKey = (string) config('services.paymongo.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo secret key is not configured.');
        }

        $memberName = trim(sprintf(
            '%s %s',
            $payment->member?->first_name ?? 'Member',
            $payment->member?->last_name ?? ''
        ));
        $description = $payment->subscription_id
            ? sprintf('Subscription payment #%d', $payment->subscription_id)
            : sprintf('Gym payment #%d', $payment->id);

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => [
                        'name' => trim($memberName) !== '' ? $memberName : sprintf('Member #%d', $payment->member_id),
                        'email' => $payment->member?->email,
                    ],
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'description' => $description,
                    'currency' => strtoupper($payment->currency),
                    'line_items' => [[
                        'currency' => strtoupper($payment->currency),
                        'amount' => (int) round(((float) $payment->amount) * 100),
                        'name' => $description,
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => config('services.paymongo.payment_methods', ['card', 'gcash', 'paymaya', 'grab_pay']),
                    'success_url' => config('services.paymongo.success_url'),
                    'cancel_url' => config('services.paymongo.cancel_url'),
                    'metadata' => [
                        'payment_id' => (string) $payment->id,
                        'subscription_id' => $payment->subscription_id ? (string) $payment->subscription_id : null,
                        'member_id' => (string) $payment->member_id,
                        'tenant_id' => (string) $payment->tenant_id,
                        'branch_id' => (string) $payment->branch_id,
                    ],
                    'reference_number' => sprintf('GYM-PAY-%d', $payment->id),
                ],
            ],
        ];

        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->asJson()
            ->post(rtrim((string) config('services.paymongo.base_url'), '/').'/checkout_sessions', $payload)
            ->throw()
            ->json();

        $attributes = Arr::get($response, 'data.attributes', []);

        return [
            'id' => Arr::get($response, 'data.id'),
            'checkout_url' => Arr::get($attributes, 'checkout_url'),
            'reference_number' => Arr::get($attributes, 'reference_number'),
            'metadata' => Arr::get($attributes, 'metadata', []),
            'response' => $response,
        ];
    }

    public function verifySignature(?string $signatureHeader, string $payload): bool
    {
        $secret = (string) config('services.paymongo.webhook_secret');

        if ($secret === '') {
            return true;
        }

        if (! $signatureHeader) {
            return false;
        }

        $segments = collect(explode(',', $signatureHeader))
            ->mapWithKeys(function (string $segment) {
                [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);

                return $key && $value ? [$key => $value] : [];
            });

        $timestamp = $segments->get('t');
        $signatures = array_filter([
            $segments->get('v1'),
            $segments->get('s'),
        ]);

        if ($signatures === []) {
            return false;
        }

        $candidates = array_filter([
            hash_hmac('sha256', $payload, $secret),
            $timestamp ? hash_hmac('sha256', $timestamp.'.'.$payload, $secret) : null,
        ]);

        foreach ($signatures as $signature) {
            foreach ($candidates as $candidate) {
                if (hash_equals($candidate, $signature)) {
                    return true;
                }
            }
        }

        return false;
    }
}