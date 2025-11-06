<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageBookings();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'invoice_email' => ['required', 'email'],
            'finance_contact' => ['nullable', 'email'],
            'is_active' => ['boolean'],
        ];
    }
}
