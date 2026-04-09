<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['super_admin', 'gym_admin', 'staff'], true);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'member_id' => ['required', 'exists:members,id'],
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'sessions_remaining' => ['nullable', 'integer', 'min:0'],
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'status' => ['required', 'in:pending,active,expired,frozen,cancelled'],
        ];
    }
}