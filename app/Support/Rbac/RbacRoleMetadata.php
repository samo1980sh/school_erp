<?php

declare(strict_types=1);

namespace App\Support\Rbac;

use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Role;

final class RbacRoleMetadata
{
    public static function displayName(Role $role): string
    {
        return self::localizedValue($role, 'display_name')
            ?: self::fallbackText($role->display_name, $role->name);
    }

    public static function description(Role $role): string
    {
        return self::localizedValue($role, 'description')
            ?: self::fallbackText($role->description, '');
    }

    public static function optionLabel(Role $role): string
    {
        $displayName = self::displayName($role);
        $technicalName = (string) $role->name;

        if ($displayName === $technicalName) {
            return $technicalName;
        }

        return "{$displayName} — {$technicalName}";
    }

    private static function localizedValue(Role $role, string $key): ?string
    {
        if (app()->getLocale() !== 'en') {
            return null;
        }

        $translationKey = "rbac_roles.roles.{$role->name}.{$key}";

        if (! Lang::has($translationKey)) {
            return null;
        }

        $value = trim((string) __($translationKey));

        return $value !== '' ? $value : null;
    }

    private static function fallbackText(mixed $value, string $default): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }
}