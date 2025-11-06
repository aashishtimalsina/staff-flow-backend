<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class Client extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'postcode',
        'finance_contact',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Get active rate card for specific job role and date
    public function getApplicableRateCard(int $jobRoleId, string $date = null)
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');

        return $this->rateCards()
            ->where('job_role_id', $jobRoleId)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    // Relationships
    public function rateCards()
    {
        return $this->hasMany(RateCard::class);
    }

    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
