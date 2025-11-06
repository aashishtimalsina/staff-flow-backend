<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function complianceDocuments()
    {
        return $this->hasMany(ComplianceDocument::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function rateCards()
    {
        return $this->hasMany(RateCard::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
