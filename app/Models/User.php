<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * User role constants
     */
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_RECRUITER = 'recruiter';
    public const ROLE_FINANCE = 'finance';
    public const ROLE_COMPLIANCE = 'compliance';
    public const ROLE_WORKER = 'worker';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar_url',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uid)) {
                $user->uid = Str::uuid()->toString();
            }
        });
    }

    // Role check methods
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isRecruiter(): bool
    {
        return $this->role === self::ROLE_RECRUITER;
    }

    public function isFinance(): bool
    {
        return $this->role === self::ROLE_FINANCE;
    }

    public function isCompliance(): bool
    {
        return $this->role === self::ROLE_COMPLIANCE;
    }

    public function isWorker(): bool
    {
        return $this->role === self::ROLE_WORKER;
    }

    // Permission check methods
    public function canAccessAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN]);
    }

    public function canManageBookings(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_RECRUITER]);
    }

    public function canManageFinance(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_FINANCE]);
    }

    public function canManageCompliance(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_COMPLIANCE]);
    }

    // Relationships
    public function candidate()
    {
        return $this->hasOne(Candidate::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdBookings()
    {
        return $this->hasMany(BookingRequest::class, 'created_by');
    }

    public function approvedTimesheets()
    {
        return $this->hasMany(Timesheet::class, 'approved_by');
    }
}
