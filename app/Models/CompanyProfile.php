<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class CompanyProfile extends Model
{
    use HasFactory, Auditable;

    protected $table = 'company_profile';

    protected $fillable = [
        'name',
        'logo_url',
        'address',
        'city',
        'postcode',
        'phone',
        'email',
        'vat_number',
        'registration_number',
    ];

    // Get formatted address
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->postcode,
        ]);

        return implode(', ', $parts);
    }

    // Static helper to get the company profile (singleton pattern)
    public static function getProfile(): ?self
    {
        return self::first();
    }

    // Static helper to update or create profile
    public static function updateOrCreateProfile(array $data): self
    {
        $profile = self::first();

        if ($profile) {
            $profile->update($data);
            return $profile;
        }

        return self::create($data);
    }
}
