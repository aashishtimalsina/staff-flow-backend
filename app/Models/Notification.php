<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'link',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Mark as read
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        $this->is_read = true;
        return $this->save();
    }

    // Mark as unread
    public function markAsUnread(): bool
    {
        if (!$this->is_read) {
            return true;
        }

        $this->is_read = false;
        return $this->save();
    }

    // Check if notification is recent (within 24 hours)
    public function isRecent(): bool
    {
        return $this->created_at->diffInHours(Carbon::now()) <= 24;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Static helper to create notification
    public static function createForUser(int $userId, string $title, string $message, string $type = null, string $link = null): self
    {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'is_read' => false,
        ]);
    }

    // Static helper to mark all as read for a user
    public static function markAllAsReadForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
