<?php

return [

    'navigation' => [
        [
            'label' => 'Workspace',
            'items' => [
                [
                    'label' => 'Operations Overview',
                    'route' => 'dashboard',
                    'icon' => 'grid',
                    'badge' => null,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business modules
    |--------------------------------------------------------------------------
    | key      → Alpine / data lookup id
    | children → sidebar options (use stable key for tables & future routes)
    */
    'modules' => [
        [
            'key' => 'ticketing',
            'title' => 'Ticketing & Reservation',
            'description' => 'Issue tickets, import details, and manage PNRs.',
            'icon' => 'ticket',
            'status' => 'active',
            'children' => [
                ['key' => 'issue-ticket', 'label' => 'Issue Ticket', 'route' => null],
                ['key' => 'import-export', 'label' => 'Import / Export Ticket Details', 'route' => null],
                ['key' => 'ticket-history', 'label' => 'Ticket History', 'route' => null],
                ['key' => 'pnr-search', 'label' => 'PNR Search', 'route' => null],
            ],
        ],
        [
            'key' => 'hotel-vouchers',
            'title' => 'Umrah & Haj Hotel Vouchers',
            'description' => 'Create vouchers and manage hotel allotments.',
            'icon' => 'building',
            'status' => 'active',
            'children' => [
                ['key' => 'create-voucher', 'label' => 'Create Hotel Voucher', 'route' => null],
                ['key' => 'manage-allotments', 'label' => 'Manage Allotments', 'route' => null],
                ['key' => 'voucher-records', 'label' => 'Voucher Records', 'route' => null],
            ],
        ],
        [
            'key' => 'study-visa',
            'title' => 'Study & Visa Consultation',
            'description' => 'Handle consultations, cases, and student records.',
            'icon' => 'globe',
            'status' => 'active',
            'children' => [
                ['key' => 'new-consultation', 'label' => 'New Consultation', 'route' => null],
                ['key' => 'visa-cases', 'label' => 'Visa Cases', 'route' => null],
                ['key' => 'student-records', 'label' => 'Student Records', 'route' => null],
            ],
        ],
        [
            'key' => 'accounts',
            'title' => 'Accounts',
            'description' => 'Invoices, payments, and financial reports.',
            'icon' => 'receipt',
            'status' => 'active',
            'children' => [
                ['key' => 'create-invoice', 'label' => 'Create Invoice', 'route' => null],
                ['key' => 'payments', 'label' => 'Payments', 'route' => null],
                ['key' => 'reports', 'label' => 'Reports', 'route' => null],
            ],
        ],
        [
            'key' => 'promotion',
            'title' => 'Promotion & Advertisements',
            'description' => 'Campaigns, ads, and performance tracking.',
            'icon' => 'chart',
            'status' => 'active',
            'children' => [
                ['key' => 'campaigns', 'label' => 'Campaigns', 'route' => null],
                ['key' => 'create-ad', 'label' => 'Create Advertisement', 'route' => null],
                ['key' => 'performance', 'label' => 'Performance', 'route' => null],
            ],
        ],
    ],

    'quick_actions' => [],

];
