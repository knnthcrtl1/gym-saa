<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipPlanRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::PLANS_MANAGE);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_type' => ['sometimes', 'in:day,week,month,year,session'],
            'duration_value' => ['sometimes', 'integer', 'min:1'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'session_limit' => ['nullable', 'integer', 'min:1'],
            'freeze_limit_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}