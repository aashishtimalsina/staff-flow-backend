<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class BookingRequest extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'client_id',
        'job_role_id',
        'location',
        'description',
        'shift_start_time',
        'shift_end_time',
        'candidates_needed',
        'client_rate',
        'worker_rate',
        'status',
        'special_requirements',
        'created_by',
    ];

    protected $casts = [
        'shift_start_time' => 'datetime',
        'shift_end_time' => 'datetime',
        'candidates_needed' => 'integer',
        'client_rate' => 'decimal:2',
        'worker_rate' => 'decimal:2',
    ];

    // Determine work type based on shift start time
    public function getWorkType(): string
    {
        $start = Carbon::parse($this->shift_start_time);
        $hour = $start->hour;

        // Check if it's a UK bank holiday
        if ($this->isUKBankHoliday($start)) {
            return 'Bank Holiday';
        }

        // Check if it's weekend (Saturday or Sunday)
        if ($start->isWeekend()) {
            return 'Weekend';
        }

        // Night shift: 8 PM to 6 AM
        if ($hour >= 20 || $hour < 6) {
            return 'Night';
        }

        return 'Day';
    }

    // Check if date is UK bank holiday
    private function isUKBankHoliday(Carbon $date): bool
    {
        // Simplified - in production, use a proper bank holiday API or database
        $bankHolidays = [
            $date->year . '-01-01', // New Year's Day
            $date->year . '-12-25', // Christmas Day
            $date->year . '-12-26', // Boxing Day
            // Add other bank holidays
        ];

        return in_array($date->format('Y-m-d'), $bankHolidays);
    }

    // Calculate shift duration in hours
    public function getShiftDurationHours(): float
    {
        $start = Carbon::parse($this->shift_start_time);
        $end = Carbon::parse($this->shift_end_time);
        return $start->diffInHours($end, true);
    }

    // Check if booking can be assigned
    public function canAssignCandidate(Candidate $candidate): array
    {
        $errors = [];

        // Check if candidate is active
        if ($candidate->status !== 'Active') {
            $errors[] = 'Candidate is not active';
        }

        // Check job role match
        if ($candidate->job_role_id !== $this->job_role_id) {
            $errors[] = 'Candidate job role does not match booking requirement';
        }

        // Check compliance
        if (!$candidate->isCompliant()) {
            $errors[] = 'Candidate is not compliant';
        }

        // Check availability
        $shiftDate = Carbon::parse($this->shift_start_time)->format('Y-m-d');
        if (!$candidate->isAvailableOn($shiftDate)) {
            $errors[] = 'Candidate is not available on this date';
        }

        // Check if already assigned
        $existingAssignment = $this->assignments()
            ->where('candidate_id', $candidate->id)
            ->where('status', '!=', 'Cancelled')
            ->exists();

        if ($existingAssignment) {
            $errors[] = 'Candidate is already assigned to this booking';
        }

        return [
            'can_assign' => empty($errors),
            'errors' => $errors,
        ];
    }

    // Check if booking is fully filled
    public function isFullyFilled(): bool
    {
        $confirmedAssignments = $this->assignments()
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->count();

        return $confirmedAssignments >= $this->candidates_needed;
    }

    // Get remaining positions
    public function getRemainingPositions(): int
    {
        $confirmedAssignments = $this->assignments()
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->count();

        return max(0, $this->candidates_needed - $confirmedAssignments);
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'Open');
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForJobRole($query, int $jobRoleId)
    {
        return $query->where('job_role_id', $jobRoleId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('shift_start_time', '>', Carbon::now());
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('shift_start_time', [$startDate, $endDate]);
    }
}
