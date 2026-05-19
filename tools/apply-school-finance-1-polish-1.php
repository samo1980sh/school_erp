<?php

declare(strict_types=1);

$resources = [
    'app/Filament/Resources/FeeTypes/FeeTypeResource.php' => [
        'label_ar' => 'نوع رسم',
        'label_en' => 'Fee type',
        'plural_ar' => 'أنواع الرسوم',
        'plural_en' => 'Fee types',
        'nav_ar' => 'أنواع الرسوم',
        'nav_en' => 'Fee types',
        'sort' => 10,
    ],
    'app/Filament/Resources/StudentFees/StudentFeeResource.php' => [
        'label_ar' => 'تفصيل رسم',
        'label_en' => 'Fee detail',
        'plural_ar' => 'تفاصيل الرسوم',
        'plural_en' => 'Fee details',
        'nav_ar' => 'تفاصيل الرسوم',
        'nav_en' => 'Fee details',
        'sort' => 30,
    ],
    'app/Filament/Resources/StudentPayments/StudentPaymentResource.php' => [
        'label_ar' => 'إيصال دفع',
        'label_en' => 'Payment receipt',
        'plural_ar' => 'إيصالات الدفع',
        'plural_en' => 'Payment receipts',
        'nav_ar' => 'إيصالات الدفع',
        'nav_en' => 'Payment receipts',
        'sort' => 40,
    ],
];

function methodReplacement(string $method, string $ar, string $en): string
{
    return <<<PHP
public static function {$method}(): string
    {
        return app()->getLocale() === 'en' ? '{$en}' : '{$ar}';
    }
PHP;
}

foreach ($resources as $path => $config) {
    if (! file_exists($path)) {
        echo "[MISSING] {$path}" . PHP_EOL;
        continue;
    }

    $content = file_get_contents($path);

    if ($content === false) {
        echo "[FAILED READ] {$path}" . PHP_EOL;
        continue;
    }

    $content = preg_replace(
        "/protected\s+static\s+\?int\s+\\\$navigationSort\s*=\s*[^;]+;/",
        "protected static ?int \$navigationSort = {$config['sort']};",
        $content
    ) ?? $content;

    $content = preg_replace(
        "/public\s+static\s+function\s+getModelLabel\s*\(\)\s*:\s*string\s*\{.*?\n\s*\}/s",
        methodReplacement('getModelLabel', $config['label_ar'], $config['label_en']),
        $content,
        1
    ) ?? $content;

    $content = preg_replace(
        "/public\s+static\s+function\s+getPluralModelLabel\s*\(\)\s*:\s*string\s*\{.*?\n\s*\}/s",
        methodReplacement('getPluralModelLabel', $config['plural_ar'], $config['plural_en']),
        $content,
        1
    ) ?? $content;

    $content = preg_replace(
        "/public\s+static\s+function\s+getNavigationLabel\s*\(\)\s*:\s*string\s*\{.*?\n\s*\}/s",
        methodReplacement('getNavigationLabel', $config['nav_ar'], $config['nav_en']),
        $content,
        1
    ) ?? $content;

    file_put_contents($path, $content);

    echo "[UPDATED] {$path}" . PHP_EOL;
}
