<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class Timesheet extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'timesheet_number',
        'assignment_id',
        'candidate_id',
        'client_id',
        'shift_start_time',
        'shift_end_time',
        'hours_worked',
        'hourly_rate',
        'total_amount',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'shift_start_time' => 'datetime',
        'shift_end_time' => 'datetime',
        'hours_worked' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Auto-generate timesheet number on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($timesheet) {
            if (empty($timesheet->timesheet_number)) {
                $timesheet->timesheet_number = self::generateTimesheetNumber();
            }
        });
    }

    // Generate unique timesheet number
    protected static function generateTimesheetNumber(): string
    {
        $prefix = 'TS';
        $date = Carbon::now()->format('Ymd');
        $lastTimesheet = self::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTimesheet ? ((int) substr($lastTimesheet->timesheet_number, -4)) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Calculate total amount
    public function calculateTotal(): float
    {
        return $this->hours_worked * $this->hourly_rate;
    }

    // Check if timesheet can be submitted
    public function canBeSubmitted(): bool
    {
        return $this->status === 'Draft' && $this->hours_worked > 0;
    }

    // Check if timesheet can be approved
    public function canBeApproved(): bool
    {
        return $this->status === 'Submitted';
    }

    // Check if timesheet can be edited
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['Draft', 'Rejected']);
    }

    // Submit timesheet
    public function submit(): bool
    {
        if (!$this->canBeSubmitted()) {
            return false;
        }

        $this->status = 'Submitted';
        return $this->save();
    }

    // Approve timesheet
    public function approve(int $userId): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->status = 'Approved';
        $this->approved_by = $userId;
        $this->approved_at = Carbon::now();
        return $this->save();
    }

    // Reject timesheet
    public function reject(string $reason = null): bool
    {
        if ($this->status !== 'Submitted') {
            return false;
        }

        $this->status = 'Rejected';
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Rejection reason: " . $reason;
        }
        return $this->save();
    }

    // Relationships
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoiceLineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'Submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'Rejected');
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
        return $query->whereBetween('shift_start_time', [$startDate, $endDate]);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'Submitted');
    }
}
