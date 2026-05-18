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
];
