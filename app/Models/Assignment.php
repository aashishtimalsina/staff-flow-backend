<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'booking_request_id',
        'candidate_id',
        'status',
        'notes',
        'check_in_time',
        'check_out_time',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    // Calculate actual hours worked
    public function getActualHoursWorked(): ?float
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        return $checkIn->diffInHours($checkOut, true);
    }

    // Check if candidate checked in
    public function hasCheckedIn(): bool
    {
        return $this->check_in_time !== null;
    }

    // Check if candidate checked out
    public function hasCheckedOut(): bool
    {
        return $this->check_out_time !== null;
    }

    // Check if assignment is completed
    public function isCompleted(): bool
    {
        return $this->status === 'Completed' && $this->hasCheckedIn() && $this->hasCheckedOut();
    }

    // Relationships
    public function bookingRequest()
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function timesheet()
    {
        return $this->hasOne(Timesheet::class);
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'Confirmed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    public function scopeForBooking($query, int $bookingId)
    {
        return $query->where('booking_request_id', $bookingId);
    }
}
