<?php

declare(strict_types=1);

namespace App\Support\Rbac;

use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RbacPermissionMetadata
{
    public static function group(Permission $permission): string
    {
        return self::localizedValue($permission, 'group')
            ?: self::fallbackText($permission->group_name, self::label('غير مصنفة', 'Ungrouped'));
    }

    public static function displayName(Permission $permission): string
    {
        return self::localizedValue($permission, 'display_name')
            ?: self::fallbackText($permission->display_name, $permission->name);
    }

    public static function description(Permission $permission): string
    {
        return self::localizedValue($permission, 'description')
            ?: self::fallbackText($permission->description, '');
    }

    public static function optionLabel(Permission $permission): string
    {
        $displayName = self::displayName($permission);
        $technicalName = (string) $permission->name;

        if ($displayName === $technicalName) {
            return $technicalName;
        }

        return "{$displayName} — {$technicalName}";
    }

    public static function groupFilterOptions(): array
    {
        $options = [];

        Permission::query()
            ->where('guard_name', 'web')
            ->whereNotNull('group_name')
            ->where('group_name', '<>', '')
            ->orderBy('group_name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each(function (Permission $permission) use (&$options): void {
                $key = (string) $permission->group_name;

                if ($key === '') {
                    return;
                }

                $options[$key] = self::group($permission);
            });

        uasort(
            $options,
            fn (string $first, string $second): int => strnatcasecmp($first, $second)
        );

        return $options;
    }

    public static function groupedSelectOptions(): array
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('group_name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => self::group($permission))
            ->mapWithKeys(function ($permissions, string $groupName): array {
                return [
                    $groupName => $permissions
                        ->mapWithKeys(fn (Permission $permission): array => [
                            $permission->getKey() => self::optionLabel($permission),
                        ])
                        ->toArray(),
                ];
            })
            ->toArray();
    }

    public static function rolePermissionsOverviewHtml(Role $role, int $visibleLimitPerGroup = 4): string
    {
        $permissions = $role->relationLoaded('permissions')
            ? $role->permissions
            : $role->permissions()
                ->orderBy('group_name')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

        if ($permissions->isEmpty()) {
            return '<span class="text-gray-500">—</span>';
        }

        $separator = app()->getLocale() === 'en' ? ', ' : '، ';

        return $permissions
            ->groupBy(fn (Permission $permission): string => self::group($permission))
            ->map(function ($items, string $groupName) use ($visibleLimitPerGroup, $separator): string {
                $visibleItems = $items
                    ->take($visibleLimitPerGroup)
                    ->map(fn (Permission $permission): string => e(self::displayName($permission)))
                    ->implode($separator);

                $remainingCount = $items->count() - $visibleLimitPerGroup;

                $moreText = $remainingCount > 0
                    ? self::label(" +{$remainingCount} صلاحيات أخرى", " +{$remainingCount} more")
                    : '';

                return sprintf(
                    '<div class="mb-1"><strong>%s</strong><span class="text-gray-500">: %s%s</span></div>',
                    e($groupName),
                    $visibleItems,
                    e($moreText)
                );
            })
            ->implode('');
    }

    private static function localizedValue(Permission $permission, string $key): ?string
    {
        if (app()->getLocale() !== 'en') {
            return null;
        }

        $translationKey = "rbac.permissions.{$permission->name}.{$key}";

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

    private static function label(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }
}