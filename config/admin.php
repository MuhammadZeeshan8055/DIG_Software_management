<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Workspace links (top of sidebar)
    |--------------------------------------------------------------------------
    */
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
    | Business modules (dashboard cards → module workspace + sidebar options)
    |--------------------------------------------------------------------------
    | key      → id used when a card is clicked
    | children → flat sidebar options shown only while that module is open
    */
    'modules' => [
        [
            'key' => 'ticketing',
            'title' => 'Ticketing & Reservation',
            'description' => 'Issue tickets, import details, and manage PNRs.',
            'icon' => 'ticket',
            'status' => 'active',
            'children' => [
                ['label' => 'Issue Ticket', 'route' => null],
                ['label' => 'Import / Export Ticket Details', 'route' => null],
                ['label' => 'Ticket History', 'route' => null],
                ['label' => 'PNR Search', 'route' => null],
            ],
        ],
        [
            'key' => 'hotel-vouchers',
            'title' => 'Umrah & Haj Hotel Vouchers',
            'description' => 'Create vouchers and manage hotel allotments.',
            'icon' => 'building',
            'status' => 'active',
            'children' => [
                ['label' => 'Create Hotel Voucher', 'route' => null],
                ['label' => 'Manage Allotments', 'route' => null],
                ['label' => 'Voucher Records', 'route' => null],
            ],
        ],
        [
            'key' => 'study-visa',
            'title' => 'Study & Visa Consultation',
            'description' => 'Handle consultations, cases, and student records.',
            'icon' => 'globe',
            'status' => 'active',
            'children' => [
                ['label' => 'New Consultation', 'route' => null],
                ['label' => 'Visa Cases', 'route' => null],
                ['label' => 'Student Records', 'route' => null],
            ],
        ],
        [
            'key' => 'accounts',
            'title' => 'Accounts',
            'description' => 'Invoices, payments, and financial reports.',
            'icon' => 'receipt',
            'status' => 'active',
            'children' => [
                ['label' => 'Create Invoice', 'route' => null],
                ['label' => 'Payments', 'route' => null],
                ['label' => 'Reports', 'route' => null],
            ],
        ],
        [
            'key' => 'promotion',
            'title' => 'Promotion & Advertisements',
            'description' => 'Campaigns, ads, and performance tracking.',
            'icon' => 'chart',
            'status' => 'active',
            'children' => [
                ['label' => 'Campaigns', 'route' => null],
                ['label' => 'Create Advertisement', 'route' => null],
                ['label' => 'Performance', 'route' => null],
            ],
        ],
    ],

    'quick_actions' => [
        // ['label' => 'Create Quotation', 'primary' => true],
    ],

];
