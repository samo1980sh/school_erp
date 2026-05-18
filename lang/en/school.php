<?php

return [
    'navigation' => [
        'system_management' => 'System Management',
    ],

    'users' => [
        'model' => 'User',
        'plural' => 'Users',
        'navigation' => 'Users',
        'title' => 'Users',
        'heading' => 'Manage Users',

        'sections' => [
            'basic' => [
                'title' => 'User Information',
                'description' => 'Manage the main login information for system users.',
            ],
            'roles' => [
                'title' => 'Roles',
                'description' => 'Assign administrative roles to the user. super_admin accounts are protected from role changes.',
            ],
        ],

        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Confirm Password',
            'new_password' => 'New Password',
            'new_password_confirmation' => 'Confirm New Password',
            'roles' => 'User Roles',
            'created_at' => 'Created At',
        ],

        'actions' => [
            'create' => 'Add User',
            'edit' => 'Edit',
            'change_password' => 'Change Password',
        ],

        'messages' => [
            'updated' => 'User updated successfully',
            'password_changed' => 'Password changed successfully',
            'protected_super_admin' => 'Main system account - protected',
            'roles_help' => 'For system safety, you cannot change your own roles or super_admin roles from this screen.',
        ],
    ],

    'roles' => [
        'model' => 'Role',
        'plural' => 'Roles',
        'navigation' => 'Roles',
        'title' => 'Roles',
        'heading' => 'Manage Roles',

        'sections' => [
            'basic' => [
                'title' => 'Role Information',
                'description' => 'Manage the role name and guard used by the permission system.',
            ],
            'permissions' => [
                'title' => 'Role Permissions',
                'description' => 'Choose the permissions assigned to this role. The super_admin role is protected and cannot be edited from the interface.',
            ],
        ],

        'fields' => [
            'name' => 'Role Name',
            'guard_name' => 'Guard',
            'permissions' => 'Permissions',
            'created_at' => 'Created At',
        ],

        'actions' => [
            'create' => 'Add Role',
            'edit' => 'Edit',
        ],

        'messages' => [
            'created' => 'Role created successfully',
            'updated' => 'Role updated successfully',
            'protected_super_admin' => 'Main system role - protected from editing',
            'permissions_help' => 'Example: users.view / users.create / users.update',
        ],
    ],
];
