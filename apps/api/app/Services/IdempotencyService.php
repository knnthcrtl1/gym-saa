<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class IdempotencyService
{
    public function claim(Request $request, string $scope, array $payload): array
    {
        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));

        if ($idempotencyKey === '') {
            return ['record' => null, 'replayed' => false];
        }

        $user = $request->user();
        $tenantId = $user?->role === 'super_admin'
            ? ($payload['tenant_id'] ?? null)
            : $user?->tenant_id;
        $requestHash = hash('sha256', json_encode($this->normalize($payload), JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($tenantId, $user, $scope, $idempotencyKey, $requestHash) {
            $record = IdempotencyKey::query()
                ->where('tenant_id', $tenantId)
                ->where('scope', $scope)
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                $record = IdempotencyKey::query()->create([
                    'tenant_id' => $tenantId,
                    'actor_id' => $user?->id,
                    'scope' => $scope,
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'status' => 'processing',
                ]);

                return ['record' => $record, 'replayed' => false];
            }

            if (! hash_equals($record->request_hash, $requestHash)) {
                throw new ConflictHttpException('This idempotency key has already been used for a different request payload.');
            }

            if ($record->status === 'completed') {
                return ['record' => $record, 'replayed' => true];
            }

            throw new ConflictHttpException('This request is already being processed.');
        });
    }

    public function complete(?IdempotencyKey $record, int $responseCode, string $resourceType, int $resourceId): void
    {
        if (! $record) {
            return;
        }

        $record->forceFill([
            'status' => 'completed',
            'response_code' => $responseCode,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ])->save();
    }

    public function forget(?IdempotencyKey $record): void
    {
        if (! $record) {
            return;
        }

        $record->delete();
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);

            foreach ($value as $key => $item) {
                $value[$key] = $this->normalize($item);
            }
        }

        return $value;
    }
}