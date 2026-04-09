<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['super_admin', 'gym_admin', 'staff'], true);
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