<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::SUBSCRIPTIONS_MANAGE);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
            'branch_id' => ['sometimes', 'exists:branches,id'],
            'member_id' => ['sometimes', 'exists:members,id'],
            'membership_plan_id' => ['sometimes', 'exists:membership_plans,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'sessions_remaining' => ['nullable', 'integer', 'min:0'],
            'payment_status' => ['sometimes', 'in:unpaid,partial,paid'],
            'status' => ['sometimes', 'in:pending,active,expired,frozen,cancelled'],
        ];
    }
}