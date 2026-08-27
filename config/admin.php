<?php

return [

    'navigation' => [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Operations Overview', 'route' => 'dashboard', 'icon' => 'grid', 'badge' => null, 'active' => true],
            ],
        ],
        // [
        //     'label' => 'Attendance Self Service',
        //     'items' => [
        //         ['label' => 'My Daily Attendance', 'route' => null, 'icon' => 'clock', 'badge' => 'Daily', 'active' => false],
        //     ],
        // ],
        // [
        //     'label' => 'Sales & CRM',
        //     'items' => [
        //         ['label' => 'Quotation Management', 'route' => null, 'icon' => 'file-text', 'badge' => 'Live', 'active' => false],
        //         ['label' => 'Complete Umrah CRM', 'route' => null, 'icon' => 'users', 'badge' => 'Live', 'active' => false],
        //     ],
        // ],
        // [
        //     'label' => 'Travel Operations',
        //     'items' => [
        //         ['label' => 'Groups & Ticketing', 'route' => null, 'icon' => 'users-group', 'badge' => 'Live', 'active' => false],
        //         ['label' => 'Tickets & PNR', 'route' => null, 'icon' => 'ticket', 'badge' => 'Live', 'active' => false],
        //         ['label' => 'Hotel Allotments', 'route' => null, 'icon' => 'building', 'badge' => 'Live', 'active' => false],
        //     ],
        // ],
    ],

    'modules' => [
        [
            'title' => 'Ticketing & Reservation',
            'description' => '',
            'icon' => 'file-text',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Umrah & Haj Hotel vouchers',
            'description' => '',
            'icon' => 'file-text',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Study & Visa consultation',
            'description' => '',
            'icon' => 'file-text',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Accounts',
            'description' => '',
            'icon' => 'file-text',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Promotion & Advertisements',
            'description' => '',
            'icon' => 'file-text',
            'status' => 'active',
            'route' => null,
        ],
    ],

    'quick_actions' => [
        // ['label' => 'Create Quotation', 'primary' => true],
        // ['label' => 'Create Hotel Voucher', 'primary' => true],
        // ['label' => 'Create Umrah Group', 'primary' => false],
        // ['label' => 'Create Invoice', 'primary' => false],
        // ['label' => 'Open All-Rounder CRM', 'primary' => false],
        // ['label' => 'View Saved Records', 'primary' => false],
    ],

];
