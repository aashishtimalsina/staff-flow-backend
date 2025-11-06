<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Get human-readable action name
    public function getActionName(): string
    {
        return match ($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default => ucfirst($this->action),
        };
    }

    // Get model name without namespace
    public function getModelName(): string
    {
        $parts = explode('\\', $this->model_type);
        return end($parts);
    }

    // Get changed fields
    public function getChangedFields(): array
    {
        if ($this->action === 'created') {
            return array_keys($this->new_values ?? []);
        }

        if ($this->action === 'deleted') {
            return array_keys($this->old_values ?? []);
        }

        if ($this->action === 'updated') {
            $oldValues = $this->old_values ?? [];
            $newValues = $this->new_values ?? [];
            return array_keys(array_intersect_key($oldValues, $newValues));
        }

        return [];
    }

    // Get changes summary
    public function getChangesSummary(): array
    {
        $summary = [];
        $changedFields = $this->getChangedFields();

        foreach ($changedFields as $field) {
            $summary[$field] = [
                'old' => $this->old_values[$field] ?? null,
                'new' => $this->new_values[$field] ?? null,
            ];
        }

        return $summary;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get the auditable model (polymorphic relationship)
    public function auditable()
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, string $modelType, int $modelId = null)
    {
        $query->where('model_type', $modelType);

        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Static helper to log activity
    public static function logActivity(
        int $userId = null,
        string $action,
        string $modelType,
        int $modelId = null,
        array $oldValues = null,
        array $newValues = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
