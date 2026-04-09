<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
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
            'member_code' => ['required', 'string', 'max:100', 'unique:members,member_code'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'qr_code_value' => ['nullable', 'string', 'max:255', 'unique:members,qr_code_value'],
            'status' => ['required', 'in:active,inactive,blocked'],
            'joined_at' => ['nullable', 'date'],
        ];
    }
}