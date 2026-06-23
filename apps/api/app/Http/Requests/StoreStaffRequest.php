<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::STAFF_MANAGE);
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['gym_admin', 'staff'])],
            'staff_role' => ['nullable', Rule::in(['owner', 'manager', 'front_desk'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(GymPermission::all())],
        ];
    }
}