<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class RateCard extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'client_id',
        'job_role_id',
        'effective_date',
        'end_date',
        'is_active',
        'client_day_rate',
        'client_night_rate',
        'client_weekend_rate',
        'client_bank_holiday_rate',
        'worker_day_rate',
        'worker_night_rate',
        'worker_weekend_rate',
        'worker_bank_holiday_rate',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'client_day_rate' => 'decimal:2',
        'client_night_rate' => 'decimal:2',
        'client_weekend_rate' => 'decimal:2',
        'client_bank_holiday_rate' => 'decimal:2',
        'worker_day_rate' => 'decimal:2',
        'worker_night_rate' => 'decimal:2',
        'worker_weekend_rate' => 'decimal:2',
        'worker_bank_holiday_rate' => 'decimal:2',
    ];

    // Get client rate for specific work type
    public function getClientRate(string $workType): float
    {
        return match ($workType) {
            'Day' => $this->client_day_rate,
            'Night' => $this->client_night_rate,
            'Weekend' => $this->client_weekend_rate,
            'Bank Holiday' => $this->client_bank_holiday_rate,
            default => $this->client_day_rate,
        };
    }

    // Get worker rate for specific work type
    public function getWorkerRate(string $workType): float
    {
        return match ($workType) {
            'Day' => $this->worker_day_rate,
            'Night' => $this->worker_night_rate,
            'Weekend' => $this->worker_weekend_rate,
            'Bank Holiday' => $this->worker_bank_holiday_rate,
            default => $this->worker_day_rate,
        };
    }

    // Calculate agency margin
    public function getMargin(string $workType): float
    {
        $clientRate = $this->getClientRate($workType);
        $workerRate = $this->getWorkerRate($workType);
        return $clientRate - $workerRate;
    }

    // Calculate margin percentage
    public function getMarginPercentage(string $workType): float
    {
        $clientRate = $this->getClientRate($workType);
        if ($clientRate == 0) {
            return 0;
        }
        $margin = $this->getMargin($workType);
        return ($margin / $clientRate) * 100;
    }

    // Check if rate card is currently active
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();
        $effectiveDate = Carbon::parse($this->effective_date);

        if ($effectiveDate->isFuture()) {
            return false;
        }

        if ($this->end_date) {
            $endDate = Carbon::parse($this->end_date);
            return $endDate->isFuture() || $endDate->isToday();
        }

        return true;
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
            ->where('effective_date', '<=', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::now());
            });
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForJobRole($query, int $jobRoleId)
    {
        return $query->where('job_role_id', $jobRoleId);
    }
}
