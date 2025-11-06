<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'job_role_id' => $this->job_role_id,
            'ni_number' => $this->ni_number,
            'dob' => $this->dob?->toDateString(),
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'county' => $this->county,
            'postcode' => $this->postcode,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'skills' => $this->skills,
            'preferred_locations' => $this->preferred_locations,
            'availability' => $this->availability,
            'status' => $this->status,
            'compliance_percentage' => $this->getCompliancePercentage(),
            'is_compliant' => $this->isCompliant(),
            'job_role' => new JobRoleResource($this->whenLoaded('jobRole')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
