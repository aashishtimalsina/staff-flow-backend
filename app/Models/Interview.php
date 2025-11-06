<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class Interview extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'candidate_id',
        'client_id',
        'job_role_id',
        'interview_time',
        'location',
        'video_link',
        'status',
        'notes',
        'feedback',
        'scheduled_by',
    ];

    protected $casts = [
        'interview_time' => 'datetime',
    ];

    // Check if interview is upcoming
    public function isUpcoming(): bool
    {
        return $this->status === 'Scheduled' &&
            Carbon::parse($this->interview_time)->isFuture();
    }

    // Check if interview is past
    public function isPast(): bool
    {
        return Carbon::parse($this->interview_time)->isPast();
    }

    // Check if interview can be rescheduled
    public function canBeRescheduled(): bool
    {
        return in_array($this->status, ['Scheduled', 'Rescheduled']) &&
            $this->isUpcoming();
    }

    // Mark as completed
    public function markAsCompleted(string $feedback = null): bool
    {
        if ($this->status !== 'Scheduled') {
            return false;
        }

        $this->status = 'Completed';
        if ($feedback) {
            $this->feedback = $feedback;
        }
        return $this->save();
    }

    // Cancel interview
    public function cancel(string $reason = null): bool
    {
        if (!$this->canBeRescheduled()) {
            return false;
        }

        $this->status = 'Cancelled';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancellation reason: " . $reason;
        }
        return $this->save();
    }

    // Reschedule interview
    public function reschedule(string $newTime): bool
    {
        if (!$this->canBeRescheduled()) {
            return false;
        }

        $this->interview_time = $newTime;
        $this->status = 'Rescheduled';
        return $this->save();
    }

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    public function scheduledBy()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'Scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'Scheduled')
            ->where('interview_time', '>', Carbon::now());
    }

    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('interview_time', [$startDate, $endDate]);
    }
}
