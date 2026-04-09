<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['super_admin', 'gym_admin', 'staff'], true);
    }

    public function rules(): array
    {
        $memberId = $this->route('member')->id;

        return [
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
            'branch_id' => ['sometimes', 'exists:branches,id'],
            'member_code' => ['sometimes', 'string', 'max:100', Rule::unique('members', 'member_code')->ignore($memberId)],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'qr_code_value' => ['nullable', 'string', 'max:255', Rule::unique('members', 'qr_code_value')->ignore($memberId)],
            'status' => ['sometimes', 'in:active,inactive,blocked'],
            'joined_at' => ['nullable', 'date'],
        ];
    }
}