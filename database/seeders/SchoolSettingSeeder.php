<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SchoolSettingSeeder extends Seeder
{
    public function run(): void
    {
        SchoolSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'school_name' => 'مدرسة النخبة الخاصة',
                'legal_name' => 'مدرسة النخبة الخاصة للتعليم الأساسي والثانوي',
                'short_name' => 'النخبة',
                'school_code' => 'ELITE-SCHOOL',
                'license_number' => 'EDU-2026-001',
                'principal_name' => 'أحمد محمود الخطيب',
                'established_year' => 2010,
                'email' => 'info@elite-school.local',
                'phone' => '+963 11 555 0000',
                'mobile' => '+963 944 555 000',
                'website' => 'https://elite-school.local',
                'country' => 'سوريا',
                'city' => 'دمشق',
                'address' => 'دمشق - المزة - شارع المدارس',
                'postal_code' => '00000',
                'logo_path' => null,
                'favicon_path' => null,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Damascus',
                'currency_code' => 'SYP',
                'is_active' => true,
            ]
        );
    }
}
