<?php

return [
    'navigation' => [
        'system_management' => 'إدارة النظام',
    ],

    'users' => [
        'model' => 'مستخدم',
        'plural' => 'المستخدمون',
        'navigation' => 'المستخدمون',
        'title' => 'المستخدمون',
        'heading' => 'إدارة المستخدمين',

        'sections' => [
            'basic' => [
                'title' => 'بيانات المستخدم',
                'description' => 'إدارة بيانات الدخول الأساسية للمستخدم داخل النظام.',
            ],
            'roles' => [
                'title' => 'الأدوار',
                'description' => 'ربط المستخدم بالأدوار الإدارية داخل النظام. حسابات super_admin محمية من تعديل الأدوار.',
            ],
        ],

        'fields' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'new_password' => 'كلمة المرور الجديدة',
            'new_password_confirmation' => 'تأكيد كلمة المرور الجديدة',
            'roles' => 'أدوار المستخدم',
            'created_at' => 'تاريخ الإنشاء',
        ],

        'actions' => [
            'create' => 'إضافة مستخدم',
            'edit' => 'تعديل',
            'change_password' => 'تغيير كلمة المرور',
        ],

        'messages' => [
            'updated' => 'تم تحديث المستخدم بنجاح',
            'password_changed' => 'تم تغيير كلمة المرور بنجاح',
            'protected_super_admin' => 'حساب نظام رئيسي - محمي',
            'roles_help' => 'لحماية النظام، لا يمكن تعديل أدوار حسابك الحالي أو حسابات super_admin من هذه الشاشة.',
        ],
    ],

    'roles' => [
        'model' => 'دور',
        'plural' => 'الأدوار',
        'navigation' => 'الأدوار',
        'title' => 'الأدوار',
        'heading' => 'إدارة الأدوار',

        'sections' => [
            'basic' => [
                'title' => 'بيانات الدور',
                'description' => 'إدارة اسم الدور والحارس المستخدم في نظام الصلاحيات.',
            ],
            'permissions' => [
                'title' => 'صلاحيات الدور',
                'description' => 'اختر الصلاحيات المرتبطة بهذا الدور. دور super_admin محمي ولا يتم تعديله من الواجهة.',
            ],
        ],

        'fields' => [
            'name' => 'اسم الدور',
            'guard_name' => 'الحارس',
            'permissions' => 'الصلاحيات',
            'created_at' => 'تاريخ الإنشاء',
        ],

        'actions' => [
            'create' => 'إضافة دور',
            'edit' => 'تعديل',
        ],

        'messages' => [
            'created' => 'تم إنشاء الدور بنجاح',
            'updated' => 'تم تحديث الدور بنجاح',
            'protected_super_admin' => 'دور النظام الرئيسي - محمي من التعديل',
            'permissions_help' => 'مثال: users.view / users.create / users.update',
        ],
    ],
];
