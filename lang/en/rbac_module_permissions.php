<?php

declare(strict_types=1);

return [
    'permissions' => [
        'students.import' => [
            'group' => 'People',
            'display_name' => 'Import students',
            'description' => 'Allows importing student data from an approved Excel template.',
        ],
        'guardians.export' => [
            'group' => 'People',
            'display_name' => 'Export guardians',
            'description' => 'Allows exporting guardian data to Excel for review or reporting.',
        ],
        'guardians.import' => [
            'group' => 'People',
            'display_name' => 'Import guardians',
            'description' => 'Allows importing guardian data from an approved Excel template.',
        ],
        'teachers.export' => [
            'group' => 'People',
            'display_name' => 'Export teachers',
            'description' => 'Allows exporting teacher data to Excel for review or reporting.',
        ],
        'teachers.import' => [
            'group' => 'People',
            'display_name' => 'Import teachers',
            'description' => 'Allows importing teacher data from an approved Excel template.',
        ],
    ],
];
