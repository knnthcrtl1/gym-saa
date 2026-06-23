<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipPlanRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::PLANS_MANAGE);
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