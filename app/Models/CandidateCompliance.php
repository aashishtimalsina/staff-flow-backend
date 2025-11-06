<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class CandidateCompliance extends Model
{
    use HasFactory, Auditable;

    protected $table = 'candidate_compliance';

    protected $fillable = [
        'candidate_id',
        'compliance_document_id',
        'file_url',
        'expiry_date',
        'status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    // Check if document is expired
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return Carbon::parse($this->expiry_date)->isPast();
    }

    // Check if document is expiring soon (within 30 days)
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        $expiryDate = Carbon::parse($this->expiry_date);
        return $expiryDate->isFuture() && $expiryDate->diffInDays(Carbon::now()) <= $days;
    }

    // Relationships
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function complianceDocument()
    {
        return $this->belongsTo(ComplianceDocument::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', Carbon::now())
            ->whereNotNull('expiry_date');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '>', Carbon::now())
            ->where('expiry_date', '<=', Carbon::now()->addDays($days))
            ->whereNotNull('expiry_date');
    }

    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }
}
