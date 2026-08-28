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
            'title' => 'Umrah & Hajj Management',
            'description' => 'Create vouchers and manage hotel allotments.',
            'icon' => 'building',
            'status' => 'active',
            'children' => [
                ['key' => 'management', 'label' => 'Management', 'route' => null],
                ['key' => 'quotations', 'label' => 'Quotations', 'route' => null],
                ['key' => 'hotel-vouchers', 'label' => 'Hotel Vouchers', 'route' => null],
                ['key' => 'hotel-allotement-system', 'label' => 'Hotel Allotement System', 'route' => null],
                ['key' => 'umrah-package', 'label' => 'Umrah Package', 'route' => null],
                ['key' => 'umrah-documents', 'label' => 'Umrah Documents', 'route' => null],
                ['key' => 'monthly-hotel-bookings-rates', 'label' => 'Voucher Records', 'route' => null],
                ['key' => 'insurance', 'label' => 'Insurance', 'route' => null],
                ['key' => 'haj-packages', 'label' => 'Haj Packages', 'route' => null],
            ],
        ],
        // [
        //     'key' => 'study-visa',
        //     'title' => 'Study & Visa Consultation',
        //     'description' => 'Handle consultations, cases, and student records.',
        //     'icon' => 'globe',
        //     'status' => 'active',
        //     'children' => [
        //         ['key' => 'new-consultation', 'label' => 'New Consultation', 'route' => null],
        //         ['key' => 'visa-cases', 'label' => 'Visa Cases', 'route' => null],
        //         ['key' => 'student-records', 'label' => 'Student Records', 'route' => null],
        //     ],
        // ],
        [
            'key' => 'travel-tourism',
            'title' => 'Travel & Tourism',
            'description' => 'Visit Visa, Packages',
            'icon' => 'chart',
            'status' => 'active',
            'children' => [
                ['key' => 'visit-visa', 'label' => 'Visit Visa', 'route' => null],
                ['key' => 'packages', 'label' => 'Packages', 'route' => null],
            ],
        ],
        [
            'key' => 'study-visit-visa',
            'title' => 'Study & Visit Visa',
            'description' => 'Visit visa, Group Tour, Student Consultancy, Client Details',
            'icon' => 'chart',
            'status' => 'active',
            'children' => [
                ['key' => 'visit-visa-consultancy', 'label' => 'Visit Visa Consultancy', 'route' => null],
                ['key' => 'group-tour-consultancy', 'label' => 'Group Tour Consultancy', 'route' => null],
                ['key' => 'student-consultancy', 'label' => 'Student Consultancy', 'route' => null],
                ['key' => 'client-details', 'label' => 'Client Details', 'route' => null],
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
