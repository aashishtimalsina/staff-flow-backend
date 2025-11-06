<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageBookings();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'job_role_id' => ['required', 'exists:job_roles,id'],
            'ni_number' => ['required', 'string', 'max:20'],
            'dob' => ['required', 'date', 'before:today'],
            'address_line1' => ['required', 'string'],
            'address_line2' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'county' => ['required', 'string'],
            'postcode' => ['required', 'string', 'max:10'],
            'emergency_contact_name' => ['required', 'string'],
            'emergency_contact_phone' => ['required', 'string'],
            'skills' => ['nullable', 'array'],
            'preferred_locations' => ['nullable', 'array'],
            'availability' => ['nullable', 'array'],
            'status' => ['sometimes', 'in:Active,Inactive,Onboarding'],
        ];
    }
}
