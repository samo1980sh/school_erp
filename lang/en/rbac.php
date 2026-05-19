<?php

declare(strict_types=1);

return [
    'permissions' => [
        'admin_panel.access' => [
            'group' => 'Admin Panel',
            'display_name' => 'Access admin panel',
            'description' => 'Allows the user to sign in to the main system administration panel.',
        ],
        'dashboard.view' => [
            'group' => 'Admin Panel',
            'display_name' => 'View dashboard',
            'description' => 'Allows the user to view the main dashboard and general indicators.',
        ],

        'users.view' => [
            'group' => 'User Management',
            'display_name' => 'View users',
            'description' => 'Allows access to the users page and viewing existing system accounts.',
        ],
        'users.create' => [
            'group' => 'User Management',
            'display_name' => 'Create users',
            'description' => 'Allows creating new user accounts inside the system.',
        ],
        'users.update' => [
            'group' => 'User Management',
            'display_name' => 'Update users',
            'description' => 'Allows updating basic user data such as name and email address.',
        ],
        'users.change_password' => [
            'group' => 'User Management',
            'display_name' => 'Change passwords',
            'description' => 'Allows changing user passwords from the administration panel.',
        ],
        'users.assign_roles' => [
            'group' => 'User Management',
            'display_name' => 'Assign user roles',
            'description' => 'Allows assigning administrative roles to users or removing them.',
        ],

        'roles.view' => [
            'group' => 'Roles and Permissions',
            'display_name' => 'View roles',
            'description' => 'Allows viewing the list of administrative roles and their definitions.',
        ],
        'roles.create' => [
            'group' => 'Roles and Permissions',
            'display_name' => 'Create roles',
            'description' => 'Allows creating new roles for distributing permissions.',
        ],
        'roles.update' => [
            'group' => 'Roles and Permissions',
            'display_name' => 'Update roles',
            'description' => 'Allows updating role names and basic data, except protected roles.',
        ],
        'roles.assign_permissions' => [
            'group' => 'Roles and Permissions',
            'display_name' => 'Assign permissions to roles',
            'description' => 'Allows selecting which permissions are attached to each administrative role.',
        ],
        'permissions.view' => [
            'group' => 'Roles and Permissions',
            'display_name' => 'View permissions',
            'description' => 'Allows viewing available system permissions and their classifications.',
        ],
        'permissions.create' => [
            'group' => 'Roles and Permissions',
            'display_name' => 'Create permissions',
            'description' => 'Allows creating new permissions when adding new modules or functions to the system.',
        ],
        'permissions.update' => [
            'group' => 'Roles and Permissions',
            'display_name' => 'Update permissions',
            'description' => 'Allows updating permission metadata, descriptions, and grouping.',
        ],

        'school_identity.view' => [
            'group' => 'School Identity and Settings',
            'display_name' => 'View school identity',
            'description' => 'Allows viewing basic school information such as name, logo, and contact details.',
        ],
        'school_identity.update' => [
            'group' => 'School Identity and Settings',
            'display_name' => 'Update school identity',
            'description' => 'Allows updating school identity data, logo, and contact details.',
        ],
        'system_settings.view' => [
            'group' => 'School Identity and Settings',
            'display_name' => 'View system settings',
            'description' => 'Allows viewing general settings that control system behavior.',
        ],
        'system_settings.update' => [
            'group' => 'School Identity and Settings',
            'display_name' => 'Update system settings',
            'description' => 'Allows updating the system general settings.',
        ],

        'academic_years.view' => [
            'group' => 'Academic Structure',
            'display_name' => 'View academic years',
            'description' => 'Allows viewing the academic years adopted by the school.',
        ],
        'academic_years.create' => [
            'group' => 'Academic Structure',
            'display_name' => 'Create academic years',
            'description' => 'Allows creating a new academic year.',
        ],
        'academic_years.update' => [
            'group' => 'Academic Structure',
            'display_name' => 'Update academic years',
            'description' => 'Allows updating academic year data and status.',
        ],
        'academic_terms.view' => [
            'group' => 'Academic Structure',
            'display_name' => 'View academic terms',
            'description' => 'Allows viewing terms or semesters linked to academic years.',
        ],
        'academic_terms.create' => [
            'group' => 'Academic Structure',
            'display_name' => 'Create academic terms',
            'description' => 'Allows creating a new academic term.',
        ],
        'academic_terms.update' => [
            'group' => 'Academic Structure',
            'display_name' => 'Update academic terms',
            'description' => 'Allows updating academic term data.',
        ],
        'educational_stages.view' => [
            'group' => 'Academic Structure',
            'display_name' => 'View educational stages',
            'description' => 'Allows viewing educational stages such as primary, middle, and secondary.',
        ],
        'educational_stages.create' => [
            'group' => 'Academic Structure',
            'display_name' => 'Create educational stages',
            'description' => 'Allows creating a new educational stage.',
        ],
        'educational_stages.update' => [
            'group' => 'Academic Structure',
            'display_name' => 'Update educational stages',
            'description' => 'Allows updating educational stage data.',
        ],
        'grades.view' => [
            'group' => 'Academic Structure',
            'display_name' => 'View grades',
            'description' => 'Allows viewing grades linked to educational stages.',
        ],
        'grades.create' => [
            'group' => 'Academic Structure',
            'display_name' => 'Create grades',
            'description' => 'Allows creating a new school grade.',
        ],
        'grades.update' => [
            'group' => 'Academic Structure',
            'display_name' => 'Update grades',
            'description' => 'Allows updating grade data.',
        ],
        'classrooms.view' => [
            'group' => 'Academic Structure',
            'display_name' => 'View classrooms',
            'description' => 'Allows viewing classrooms or school rooms.',
        ],
        'classrooms.create' => [
            'group' => 'Academic Structure',
            'display_name' => 'Create classrooms',
            'description' => 'Allows creating a new classroom or school room.',
        ],
        'classrooms.update' => [
            'group' => 'Academic Structure',
            'display_name' => 'Update classrooms',
            'description' => 'Allows updating classroom and room data.',
        ],
        'sections.view' => [
            'group' => 'Academic Structure',
            'display_name' => 'View sections',
            'description' => 'Allows viewing class sections linked to grades.',
        ],
        'sections.create' => [
            'group' => 'Academic Structure',
            'display_name' => 'Create sections',
            'description' => 'Allows creating a new class section.',
        ],
        'sections.update' => [
            'group' => 'Academic Structure',
            'display_name' => 'Update sections',
            'description' => 'Allows updating class section data.',
        ],

        'students.view' => [
            'group' => 'People',
            'display_name' => 'View students',
            'description' => 'Allows viewing student data and basic student profiles.',
        ],
        'students.create' => [
            'group' => 'People',
            'display_name' => 'Create students',
            'description' => 'Allows creating new student profiles.',
        ],
        'students.update' => [
            'group' => 'People',
            'display_name' => 'Update students',
            'description' => 'Allows updating registered student data.',
        ],
        'students.export' => [
            'group' => 'People',
            'display_name' => 'Export students',
            'description' => 'Allows exporting student data for reports or Excel files.',
        ],
        'guardians.view' => [
            'group' => 'People',
            'display_name' => 'View guardians',
            'description' => 'Allows viewing guardian data linked to students.',
        ],
        'guardians.create' => [
            'group' => 'People',
            'display_name' => 'Create guardians',
            'description' => 'Allows creating new guardian profiles.',
        ],
        'guardians.update' => [
            'group' => 'People',
            'display_name' => 'Update guardians',
            'description' => 'Allows updating guardian data.',
        ],
        'teachers.view' => [
            'group' => 'People',
            'display_name' => 'View teachers',
            'description' => 'Allows viewing teacher data.',
        ],
        'teachers.create' => [
            'group' => 'People',
            'display_name' => 'Create teachers',
            'description' => 'Allows creating new teacher profiles.',
        ],
        'teachers.update' => [
            'group' => 'People',
            'display_name' => 'Update teachers',
            'description' => 'Allows updating teacher data.',
        ],
        'employees.view' => [
            'group' => 'People',
            'display_name' => 'View employees',
            'description' => 'Allows viewing administrative employee data.',
        ],
        'employees.create' => [
            'group' => 'People',
            'display_name' => 'Create employees',
            'description' => 'Allows creating new administrative employee profiles.',
        ],
        'employees.update' => [
            'group' => 'People',
            'display_name' => 'Update employees',
            'description' => 'Allows updating administrative employee data.',
        ],

        'enrollments.view' => [
            'group' => 'Registration and Enrollment',
            'display_name' => 'View enrollments',
            'description' => 'Allows viewing student enrollment records in grades and sections.',
        ],
        'enrollments.create' => [
            'group' => 'Registration and Enrollment',
            'display_name' => 'Create enrollments',
            'description' => 'Allows enrolling a student in an academic year, grade, and section.',
        ],
        'enrollments.update' => [
            'group' => 'Registration and Enrollment',
            'display_name' => 'Update enrollments',
            'description' => 'Allows updating student enrollment status or details.',
        ],

        'attendance.view' => [
            'group' => 'Attendance',
            'display_name' => 'View attendance',
            'description' => 'Allows viewing student attendance and absence records.',
        ],
        'attendance.create' => [
            'group' => 'Attendance',
            'display_name' => 'Record attendance',
            'description' => 'Allows entering attendance and absence records.',
        ],
        'attendance.update' => [
            'group' => 'Attendance',
            'display_name' => 'Update attendance',
            'description' => 'Allows updating attendance and absence records.',
        ],
        'attendance.reports' => [
            'group' => 'Attendance',
            'display_name' => 'Attendance reports',
            'description' => 'Allows viewing attendance and absence reports.',
        ],

        'subjects.view' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'View subjects',
            'description' => 'Allows viewing school subjects.',
        ],
        'subjects.create' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'Create subjects',
            'description' => 'Allows adding new school subjects.',
        ],
        'subjects.update' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'Update subjects',
            'description' => 'Allows updating school subject data.',
        ],
        'exams.view' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'View exams',
            'description' => 'Allows viewing exams and tests.',
        ],
        'exams.create' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'Create exams',
            'description' => 'Allows creating a new exam or test.',
        ],
        'exams.update' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'Update exams',
            'description' => 'Allows updating exam and test data.',
        ],
        'marks.view' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'View marks',
            'description' => 'Allows viewing student marks.',
        ],
        'marks.create' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'Enter marks',
            'description' => 'Allows entering student marks.',
        ],
        'marks.update' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'Update marks',
            'description' => 'Allows updating student marks.',
        ],
        'marks.reports' => [
            'group' => 'Subjects, Exams, and Marks',
            'display_name' => 'Marks reports',
            'description' => 'Allows viewing marks and results reports.',
        ],

        'fees.view' => [
            'group' => 'Finance and Fees',
            'display_name' => 'View fees',
            'description' => 'Allows viewing financial fees assigned to students.',
        ],
        'fees.create' => [
            'group' => 'Finance and Fees',
            'display_name' => 'Create fees',
            'description' => 'Allows adding new fees or financial items.',
        ],
        'fees.update' => [
            'group' => 'Finance and Fees',
            'display_name' => 'Update fees',
            'description' => 'Allows updating fees and financial items.',
        ],
        'fees.payments' => [
            'group' => 'Finance and Fees',
            'display_name' => 'Record payments',
            'description' => 'Allows recording student payments and updating payment status.',
        ],
        'fees.reports' => [
            'group' => 'Finance and Fees',
            'display_name' => 'Fees reports',
            'description' => 'Allows viewing fees and financial collection reports.',
        ],

        'transport.view' => [
            'group' => 'School Transport',
            'display_name' => 'View transport',
            'description' => 'Allows viewing school transport data and routes.',
        ],
        'transport.create' => [
            'group' => 'School Transport',
            'display_name' => 'Create transport data',
            'description' => 'Allows adding transport routes or related transport data.',
        ],
        'transport.update' => [
            'group' => 'School Transport',
            'display_name' => 'Update transport data',
            'description' => 'Allows updating school transport data.',
        ],

        'documents.view' => [
            'group' => 'Documents and Reports',
            'display_name' => 'View documents',
            'description' => 'Allows viewing student or school documents.',
        ],
        'documents.create' => [
            'group' => 'Documents and Reports',
            'display_name' => 'Create documents',
            'description' => 'Allows adding new documents.',
        ],
        'documents.update' => [
            'group' => 'Documents and Reports',
            'display_name' => 'Update documents',
            'description' => 'Allows updating document data.',
        ],
        'reports.view' => [
            'group' => 'Documents and Reports',
            'display_name' => 'View reports',
            'description' => 'Allows viewing system reports.',
        ],
        'reports.export' => [
            'group' => 'Documents and Reports',
            'display_name' => 'Export reports',
            'description' => 'Allows exporting and printing reports.',
        ],

        'teacher_portal.access' => [
            'group' => 'Portals',
            'display_name' => 'Access teacher portal',
            'description' => 'Allows the user to access the teacher panel or portal when it is created later.',
        ],
        'guardian_portal.access' => [
            'group' => 'Portals',
            'display_name' => 'Access guardian portal',
            'description' => 'Allows the user to access the guardian panel or portal when it is created later.',
        ],
        'student_portal.access' => [
            'group' => 'Portals',
            'display_name' => 'Access student portal',
            'description' => 'Allows the user to access the student panel or portal when it is created later.',
        ],
    ],
];