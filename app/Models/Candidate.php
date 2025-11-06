<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Candidate extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'job_role_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'ni_number',
        'address',
        'city',
        'postcode',
        'skills',
        'locations',
        'availability',
        'status',
    ];

    protected $casts = [
        'skills' => 'array',
        'locations' => 'array',
        'availability' => 'array',
        'dob' => 'date',
    ];

    protected $appends = ['full_name'];

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Check if candidate is available on a specific date
    public function isAvailableOn(string $date): bool
    {
        if (empty($this->availability)) {
            return false;
        }
        return in_array($date, $this->availability);
    }

    // Check if candidate is compliant
    public function isCompliant(): bool
    {
        $requiredDocs = $this->jobRole->complianceDocuments()->where('is_required', true)->count();

        if ($requiredDocs === 0) {
            return true;
        }

        $approvedDocs = $this->complianceDocuments()
            ->where('status', 'Approved')
            ->whereHas('complianceDocument', function ($q) {
                $q->where('is_required', true);
            })
            ->count();

        return $requiredDocs === $approvedDocs && $requiredDocs > 0;
    }

    // Get compliance percentage
    public function getCompliancePercentage(): int
    {
        $requiredDocs = $this->jobRole->complianceDocuments()->where('is_required', true)->count();

        if ($requiredDocs === 0) {
            return 100;
        }

        $approvedDocs = $this->complianceDocuments()
            ->where('status', 'Approved')
            ->whereHas('complianceDocument', function ($q) {
                $q->where('is_required', true);
            })
            ->count();

        return (int) (($approvedDocs / $requiredDocs) * 100);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    public function complianceDocuments()
    {
        return $this->hasMany(CandidateCompliance::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeCompliant($query)
    {
        return $query->whereHas('complianceDocuments', function ($q) {
            $q->where('status', 'Approved')
                ->whereHas('complianceDocument', function ($q2) {
                    $q2->where('is_required', true);
                });
        });
    }

    public function scopeForJobRole($query, int $jobRoleId)
    {
        return $query->where('job_role_id', $jobRoleId);
    }

    public function scopeAvailableOn($query, string $date)
    {
        return $query->whereJsonContains('availability', $date);
    }
}
