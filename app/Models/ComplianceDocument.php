<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_role_id',
        'document_name',
        'is_required',
        'requires_expiry',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'requires_expiry' => 'boolean',
    ];

    // Relationships
    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    public function candidateCompliance()
    {
        return $this->hasMany(CandidateCompliance::class);
    }

    // Scopes
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeForJobRole($query, int $jobRoleId)
    {
        return $query->where('job_role_id', $jobRoleId);
    }
}
