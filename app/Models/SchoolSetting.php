<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'school_name',
        'legal_name',
        'short_name',
        'school_code',
        'license_number',
        'principal_name',
        'established_year',
        'email',
        'phone',
        'mobile',
        'website',
        'country',
        'city',
        'address',
        'postal_code',
        'logo_path',
        'favicon_path',
        'default_locale',
        'timezone',
        'currency_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'established_year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }
}
