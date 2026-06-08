<?php 

return [

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    'allowed_referers' => array_map('trim', explode(',', env('ALLOWED_REFERER', 'localhost,127.0.0.1,http://localhost:3000,http://127.0.0.1:8000,aquilastech.com,https://aquilastech.com,http://aquilastech.com'))),

    'admin_email' => env('ADMIN_EMAIL', 'ahsanhanif99@gmail.com'),

    'staff_privileges' => [
        'supervisor' => [
            'live_op' => ['list'],
            'staff' => ['list', 'create', 'update', 'delete', 'detail', 'document', 'compliance', 'privilege', 'salary'],
            'shift' => ['list', 'create', 'update', 'delete', 'notes'],
            'pay_group' => ['list', 'create', 'update', 'delete'],
            'service_group' => ['list', 'create', 'update', 'delete'],
            'invoice' => ['list', 'create', 'update', 'delete', 'complete'],
            'timesheet' => ['list', 'create', 'update', 'delete'],
            'static_site' => ['list', 'create', 'update', 'delete', 'contact', 'preference', 'checkpoint', 'document', 'access_code'],
            'patrol_site' => ['list', 'create', 'update', 'delete', 'contact', 'preference', 'checkpoint', 'document', 'access_code'],
            'customer' => ['list', 'create', 'update', 'delete', 'detail', 'site', 'contact', 'invoice_profile'],
            'digital_occurence_log' => ['list', 'update', 'delete']
        ],
        
        'security_officer' => [
            'shift' => ['list'],
            'static_site' => ['list'],
            'patrol_site' => ['list']
        ]
    ],
];