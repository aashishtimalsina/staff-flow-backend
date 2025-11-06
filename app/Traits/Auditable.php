<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            self::logActivity('created', $model, null, $model->toArray());
        });

        static::updated(function ($model) {
            self::logActivity('updated', $model, $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model) {
            self::logActivity('deleted', $model, $model->toArray(), null);
        });
    }

    protected static function logActivity($action, $model, $oldValues, $newValues)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
