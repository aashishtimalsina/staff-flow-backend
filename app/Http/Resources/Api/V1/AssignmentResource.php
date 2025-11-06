<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_request_id' => $this->booking_request_id,
            'candidate_id' => $this->candidate_id,
            'status' => $this->status,
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'booking' => new BookingRequestResource($this->whenLoaded('bookingRequest')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
