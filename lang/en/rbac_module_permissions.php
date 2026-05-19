<?php

declare(strict_types=1);

return [
    'permissions' => [
        'students.import' => [
            'group' => 'People',
            'display_name' => 'Import students',
            'description' => 'Allows importing student data from Excel using the approved template.',
        ],
        'guardians.export' => [
            'group' => 'People',
            'display_name' => 'Export guardians',
            'description' => 'Allows exporting guardian data to Excel for review or reporting.',
        ],
        'guardians.import' => [
            'group' => 'People',
            'display_name' => 'Import guardians',
            'description' => 'Allows importing guardian data from Excel using the approved template.',
        ],
    ],
];
