<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentIntentRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::PAYMENTS_MANAGE);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'member_id' => ['required', 'exists:members,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
        ];
    }
}