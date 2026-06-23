<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::MEMBERS_MANAGE);
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->role === 'super_admin'
            ? $this->integer('tenant_id')
            : $this->user()?->tenant_id;

        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'member_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('members', 'member_code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('members', 'email')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'qr_code_value' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('members', 'qr_code_value')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'status' => ['required', 'in:active,inactive,blocked'],
            'joined_at' => ['nullable', 'date'],
        ];
    }
}