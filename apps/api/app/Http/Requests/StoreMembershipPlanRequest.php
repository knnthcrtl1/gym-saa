<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['super_admin', 'gym_admin'], true);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_type' => ['required', 'in:day,week,month,year,session'],
            'duration_value' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'session_limit' => ['nullable', 'integer', 'min:1'],
            'freeze_limit_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}