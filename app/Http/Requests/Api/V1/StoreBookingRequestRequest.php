<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageBookings();
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'job_role_id' => ['required', 'exists:job_roles,id'],
            'shift_start_time' => ['required', 'date', 'after:now'],
            'shift_end_time' => ['required', 'date', 'after:shift_start_time'],
            'location' => ['required', 'string'],
            'candidates_needed' => ['required', 'integer', 'min:1'],
            'requirements' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:Open,Filled,Cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'shift_start_time.after' => 'Shift start time must be in the future',
            'shift_end_time.after' => 'Shift end time must be after shift start time',
        ];
    }
}
