<?php

return [

    'navigation' => [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Operations Overview', 'route' => 'dashboard', 'icon' => 'grid', 'badge' => null, 'active' => true],
            ],
        ],
        [
            'label' => 'Attendance Self Service',
            'items' => [
                ['label' => 'My Daily Attendance', 'route' => null, 'icon' => 'clock', 'badge' => 'Daily', 'active' => false],
            ],
        ],
        [
            'label' => 'Sales & CRM',
            'items' => [
                ['label' => 'Quotation Management', 'route' => null, 'icon' => 'file-text', 'badge' => 'Live', 'active' => false],
                ['label' => 'Complete Umrah CRM', 'route' => null, 'icon' => 'users', 'badge' => 'Live', 'active' => false],
            ],
        ],
        [
            'label' => 'Travel Operations',
            'items' => [
                ['label' => 'Groups & Ticketing', 'route' => null, 'icon' => 'users-group', 'badge' => 'Live', 'active' => false],
                ['label' => 'Tickets & PNR', 'route' => null, 'icon' => 'ticket', 'badge' => 'Live', 'active' => false],
                ['label' => 'Hotel Allotments', 'route' => null, 'icon' => 'building', 'badge' => 'Live', 'active' => false],
            ],
        ],
    ],

    'modules' => [
        [
            'title' => 'Quotation Management',
            'description' => 'Create, save, review, and print standardized Umrah package quotations.',
            'icon' => 'file-text',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Complete Umrah CRM',
            'description' => 'Manage leads, mutamers, groups, packages, visa, hotels, tickets, transport, and accounts.',
            'icon' => 'users',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Groups & Ticketing',
            'description' => 'Manage groups, passenger strength, PNRs, and coordinated departure movements.',
            'icon' => 'users-group',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Tickets & PNR Management',
            'description' => 'Manage airline, sector, flights, ticketing deadlines, baggage, and ticket status.',
            'icon' => 'plane',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Hotel Allotment System',
            'description' => 'Track hotel vouchers, confirmations, rooms, beds, and stay dates.',
            'icon' => 'building',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Umrah Hotel Voucher',
            'description' => 'Create branded multi-hotel vouchers with passengers, stays, flights, transfers, and confirmations.',
            'icon' => 'receipt',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Umrah Package Management',
            'description' => 'Maintain package hotels, duration, airline, rates, and included services.',
            'icon' => 'package',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Monthly Umrah Hotel Booking Rates',
            'description' => 'Publish monthly Makkah and Madinah hotel rates, visa rates, transport, and package notes.',
            'icon' => 'chart',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Visa Module',
            'description' => 'Manage Visit Visa, Work Visa, and Study Visa applications with customer-linked documents and status.',
            'icon' => 'globe',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Insurance Module',
            'description' => 'Coordinate passenger coverage, providers, validity dates, and policy documentation.',
            'icon' => 'shield',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Transport Module',
            'description' => 'Plan airport, hotel, and Ziyarat movements with vehicle and driver details.',
            'icon' => 'bus',
            'status' => 'active',
            'route' => null,
        ],
        [
            'title' => 'Add Mutamers',
            'description' => 'Add and manage Mutammer identity, passport, family, MOFA, GRP, PNR, and document details.',
            'icon' => 'user-plus',
            'status' => 'active',
            'route' => null,
        ],
    ],

    'quick_actions' => [
        ['label' => 'Create Quotation', 'primary' => true],
        ['label' => 'Create Hotel Voucher', 'primary' => true],
        ['label' => 'Create Umrah Group', 'primary' => false],
        ['label' => 'Create Invoice', 'primary' => false],
        ['label' => 'Open All-Rounder CRM', 'primary' => false],
        ['label' => 'View Saved Records', 'primary' => false],
    ],

];
