<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * User role constants
     */
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * UK States/Regions constants
     */
    public const STATE_ENGLAND = 'england';
    public const STATE_SCOTLAND = 'scotland';
    public const STATE_WALES = 'wales';
    public const STATE_NORTHERN_IRELAND = 'northern_ireland';

    /**
     * Get all UK states
     */
    public static function getUKStates(): array
    {
        return [
            self::STATE_ENGLAND => 'England',
            self::STATE_SCOTLAND => 'Scotland',
            self::STATE_WALES => 'Wales',
            self::STATE_NORTHERN_IRELAND => 'Northern Ireland',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_verified',
        'ip_address',
        'last_login',
        'oauth_id',
        'photo',
        'address',
        'state',
        'city',
        'postal_code',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'is_verified' => 'boolean',
            'last_login' => 'datetime',
        ];
    }
}
