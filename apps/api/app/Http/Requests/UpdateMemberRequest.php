<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::MEMBERS_MANAGE);
    }

    public function rules(): array
    {
        $memberId = $this->route('member')->id;
        $tenantId = $this->user()?->role === 'super_admin'
            ? ($this->has('tenant_id') ? $this->integer('tenant_id') : $this->route('member')->tenant_id)
            : $this->user()?->tenant_id;

        return [
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
            'branch_id' => ['sometimes', 'exists:branches,id'],
            'member_code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('members', 'member_code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($memberId),
            ],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('members', 'email')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($memberId),
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
                Rule::unique('members', 'qr_code_value')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($memberId),
            ],
            'status' => ['sometimes', 'in:active,inactive,blocked'],
            'joined_at' => ['nullable', 'date'],
        ];
    }
}