<?php

declare(strict_types=1);

use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Filament\Resources\StudentFees\StudentFeeResource;
use App\Filament\Resources\StudentPayments\StudentPaymentResource;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$resources = [
    FeeTypeResource::class,
    StudentFeeResource::class,
    StudentPaymentResource::class,
];

$report = [
    'tables' => [
        'fee_types' => Schema::hasTable('fee_types'),
        'student_fees' => Schema::hasTable('student_fees'),
        'student_payments' => Schema::hasTable('student_payments'),
    ],
    'resources' => [],
];

foreach ($resources as $resource) {
    $reflection = new ReflectionClass($resource);

    $report['resources'][$resource] = [
        'has_form_method' => $reflection->hasMethod('form'),
        'has_navigation_group_method' => $reflection->hasMethod('getNavigationGroup'),
        'has_navigation_label_method' => $reflection->hasMethod('getNavigationLabel'),
    ];
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$hasErrors = in_array(false, $report['tables'], true);

foreach ($report['resources'] as $checks) {
    if (in_array(false, $checks, true)) {
        $hasErrors = true;
        break;
    }
}

exit($hasErrors ? 1 : 0);
