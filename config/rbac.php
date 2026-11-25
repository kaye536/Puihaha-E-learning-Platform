<?php

/*
|--------------------------------------------------------------------------
| Role-Based Access Control
|--------------------------------------------------------------------------
|
| Every permission below is registered as a Laravel Gate, so it works with
| `->middleware('can:members.manage')`, `@can('members.manage')` and
| `$user->can('members.manage')`. Roles are assigned in the users table
| (`role` column). The '*' permission grants everything.
|
*/

return [

    'default_role' => 'viewer',

    'permissions' => [
        'dashboard.view' => 'View the dashboard',
        'members.view' => 'View members, histories and printable records',
        'members.manage' => 'Register, edit, activate/deactivate and delete members',
        'catalog.view' => 'View membership categories and levels',
        'catalog.manage' => 'Add, edit and delete categories and levels',
        'transactions.view' => 'View applications and renewals',
        'transactions.create' => 'Create applications and renewals',
        'transactions.approve' => 'Approve or cancel transactions',
        'payments.view' => 'View payments and official receipts',
        'payments.record' => 'Record payments',
        'users.manage' => 'Manage staff accounts and roles',

        // E-learning (staff side)
        'courses.view' => 'View courses, lessons, quizzes and learner progress',
        'courses.manage' => 'Create, edit and delete courses, lessons and quiz questions',

        // Member portal (members signing in to see their own record)
        'portal.view' => 'View their own membership, history and receipts',
        'portal.renew' => 'Request a renewal of their own membership',
        'portal.profile' => 'Update their own phone number and address',
        'learning.access' => 'Take courses, quizzes and download their own certificates',
    ],

    'roles' => [
        'admin' => [
            'label' => 'Administrator',
            'description' => 'Full access, including staff accounts, categories and levels.',
            'permissions' => ['*'],
        ],
        'registrar' => [
            'label' => 'Registrar',
            'description' => 'Registers members and handles applications, renewals and approvals.',
            'permissions' => [
                'dashboard.view',
                'members.view', 'members.manage',
                'catalog.view',
                'transactions.view', 'transactions.create', 'transactions.approve',
                'payments.view',
                'courses.view',
            ],
        ],
        'cashier' => [
            'label' => 'Cashier',
            'description' => 'Records payments and prints official receipts.',
            'permissions' => [
                'dashboard.view',
                'members.view',
                'transactions.view',
                'payments.view', 'payments.record',
            ],
        ],
        'instructor' => [
            'label' => 'Instructor',
            'description' => 'Builds e-learning courses, lessons and quizzes and follows learner progress.',
            'permissions' => [
                'dashboard.view',
                'courses.view', 'courses.manage',
            ],
        ],
        'member' => [
            'label' => 'Member',
            'description' => 'A member signing in to the portal. Sees only their own record.',
            'staff' => false, // not a staff role: created automatically when an application is approved
            'permissions' => ['portal.view', 'portal.renew', 'portal.profile', 'learning.access'],
        ],
        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Read-only access to members, transactions and payments.',
            'permissions' => [
                'dashboard.view',
                'members.view',
                'catalog.view',
                'transactions.view',
                'payments.view',
                'courses.view',
            ],
        ],
    ],
];
