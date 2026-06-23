<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::SUBSCRIPTIONS_MANAGE);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'member_id' => ['required', 'exists:members,id'],
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sessions_remaining' => ['nullable', 'integer', 'min:0'],
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'status' => ['required', 'in:pending,active,expired,frozen,cancelled'],
        ];
    }
}