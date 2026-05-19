<?php

declare(strict_types=1);

return [
    'roles' => [
        'super_admin' => [
            'display_name' => 'Main System Administrator',
            'description' => 'Has full access to the entire system and is reserved for the protected main account only.',
        ],
        'system_admin' => [
            'display_name' => 'System Administrator',
            'description' => 'Manages system settings, users, roles, and permissions without being the protected main account.',
        ],
        'school_admin' => [
            'display_name' => 'School Administrator',
            'description' => 'Manages the main school administrative operations and follows operational users and data.',
        ],
        'academic_manager' => [
            'display_name' => 'Academic Manager',
            'description' => 'Manages the academic structure, academic years, stages, grades, sections, subjects, and exams.',
        ],
        'registrar' => [
            'display_name' => 'Registrar',
            'description' => 'Manages students, guardians, enrollment records, and related registration updates.',
        ],
        'accountant' => [
            'display_name' => 'Accountant',
            'description' => 'Manages fees, payments, and financial reports related to students and the school.',
        ],
        'teacher' => [
            'display_name' => 'Teacher',
            'description' => 'Represents a teacher account and will later be used to access the teacher panel or portal according to assigned permissions.',
        ],
        'guardian' => [
            'display_name' => 'Guardian',
            'description' => 'Represents a guardian account and will later access the guardian portal with data scoped to linked students.',
        ],
        'student' => [
            'display_name' => 'Student',
            'description' => 'Represents a student account and will later access the student portal with data scoped only to that student.',
        ],
        'limited_admin' => [
            'display_name' => 'Limited Administrator',
            'description' => 'A limited administrative account used for testing or for staff members who need narrow administrative access.',
        ],
    ],
];