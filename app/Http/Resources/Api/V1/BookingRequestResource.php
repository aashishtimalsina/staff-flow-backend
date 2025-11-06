<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'job_role_id' => $this->job_role_id,
            'shift_start_time' => $this->shift_start_time->toIso8601String(),
            'shift_end_time' => $this->shift_end_time->toIso8601String(),
            'location' => $this->location,
            'candidates_needed' => $this->candidates_needed,
            'requirements' => $this->requirements,
            'notes' => $this->notes,
            'status' => $this->status,
            'work_type' => $this->getWorkType(),
            'created_by' => $this->created_by,
            'client' => new ClientResource($this->whenLoaded('client')),
            'job_role' => new JobRoleResource($this->whenLoaded('jobRole')),
            'assignments' => AssignmentResource::collection($this->whenLoaded('assignments')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
