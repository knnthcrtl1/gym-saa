<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesGymPermission;
use App\Support\GymPermission;
use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
{
    use AuthorizesGymPermission;

    public function authorize(): bool
    {
        return $this->userCan(GymPermission::PAYMENTS_MANAGE);
    }

    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}