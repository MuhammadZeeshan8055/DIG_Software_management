<?php

/**
 * Module workspace data (stats, charts, tables).
 *
 * Edit ONLY this file to change numbers / labels / rows.
 * Key names must match config/admin.php module + child keys.
 *
 * Later: replace values from a controller query — keep the same shape.
 */
return [

    'ticketing' => [
        'stats' => [
            ['label' => 'Tickets Issued', 'value' => '1,284', 'hint' => '+12% this week', 'tone' => 'blue'],
            ['label' => 'Rejected', 'value' => '42', 'hint' => '3.2% of total', 'tone' => 'red'],
            ['label' => 'Cancelled', 'value' => '67', 'hint' => '5.1% of total', 'tone' => 'amber'],
            ['label' => 'Pending', 'value' => '118', 'hint' => 'Awaiting action', 'tone' => 'navy'],
        ],
        'trend' => [
            'title' => 'Weekly ticket volume',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [42, 58, 51, 74, 68, 39, 47],
        ],
        'share' => [
            'title' => 'Status distribution',
            'items' => [
                ['label' => 'Issued', 'value' => 72, 'tone' => 'blue'],
                ['label' => 'Pending', 'value' => 14, 'tone' => 'navy'],
                ['label' => 'Cancelled', 'value' => 8, 'tone' => 'amber'],
                ['label' => 'Rejected', 'value' => 6, 'tone' => 'red'],
            ],
        ],
        'tables' => [
            'issue-ticket' => [
                'title' => 'Issue Ticket',
                'columns' => ['Ticket No', 'Passenger', 'Route', 'Date', 'Status'],
                'rows' => [
                    ['TK-10421', 'Ahmed Khan', 'LHE → JED', '27 Aug 2026', 'Draft'],
                    ['TK-10422', 'Sara Ali', 'ISB → DXB', '27 Aug 2026', 'Ready'],
                    ['TK-10423', 'Bilal Hussain', 'KHI → MED', '28 Aug 2026', 'Draft'],
                    ['TK-10424', 'Fatima Noor', 'LHE → RUH', '28 Aug 2026', 'Ready'],
                ],
            ],
            'import-ticket-details' => [
                'title' => 'Import Ticket Details',
                'columns' => ['File', 'Type', 'Records', 'Uploaded By', 'Status'],
                'rows' => [
                    ['tickets_aug.csv', 'Import', '120', 'Admin', 'Completed'],
                    ['pnr_batch.xlsx', 'Import', '45', 'TE Test', 'Processing'],
                    ['export_week.csv', 'Export', '310', 'Admin', 'Completed'],
                    ['failed_rows.csv', 'Import', '8', 'Staff', 'Failed'],
                ],
            ],
            'ticket-history' => [
                'title' => 'Ticket History',
                'columns' => ['Ticket No', 'Passenger', 'Issued On', 'Agent', 'Status'],
                'rows' => [
                    ['TK-10390', 'Usman Raza', '20 Aug 2026', 'Ayesha', 'Issued'],
                    ['TK-10391', 'Hina Malik', '21 Aug 2026', 'Omar', 'Cancelled'],
                    ['TK-10392', 'Zain Abbas', '22 Aug 2026', 'Ayesha', 'Issued'],
                    ['TK-10393', 'Nadia Shah', '23 Aug 2026', 'Omar', 'Rejected'],
                ],
            ],
            'pnr-search' => [
                'title' => 'PNR Search',
                'columns' => ['PNR', 'Airline', 'Passenger', 'Sector', 'Status'],
                'rows' => [
                    ['AB12CD', 'SV', 'Ahmed Khan', 'LHE-JED', 'Confirmed'],
                    ['XY98ZZ', 'EK', 'Sara Ali', 'ISB-DXB', 'Hold'],
                    ['PQ45LM', 'QR', 'Bilal Hussain', 'KHI-DOH', 'Confirmed'],
                    ['GH77NK', 'PK', 'Fatima Noor', 'LHE-JED', 'Cancelled'],
                ],
            ],
        ],
    ],

    'hotel-vouchers' => [
        'stats' => [
            ['label' => 'Active Vouchers', 'value' => '356', 'hint' => '+8 this week', 'tone' => 'blue'],
            ['label' => 'Allotments', 'value' => '89', 'hint' => '12 hotels', 'tone' => 'navy'],
            ['label' => 'Used Today', 'value' => '24', 'hint' => 'On track', 'tone' => 'amber'],
            ['label' => 'Expired', 'value' => '11', 'hint' => 'Needs review', 'tone' => 'red'],
        ],
        'trend' => [
            'title' => 'Weekly voucher activity',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [18, 22, 16, 28, 31, 12, 19],
        ],
        'share' => [
            'title' => 'Voucher status mix',
            'items' => [
                ['label' => 'Active', 'value' => 58, 'tone' => 'blue'],
                ['label' => 'Allotments', 'value' => 22, 'tone' => 'navy'],
                ['label' => 'Used', 'value' => 14, 'tone' => 'amber'],
                ['label' => 'Expired', 'value' => 6, 'tone' => 'red'],
            ],
        ],
        'tables' => [
            'management' => [
                'title' => 'Management',
                'columns' => ['Ref', 'Group', 'Hotel', 'Nights', 'Status'],
                'rows' => [
                    ['MG-1101', 'Group A', 'Makarem Ajyad', '5', 'Active'],
                    ['MG-1102', 'Group B', 'Pullman ZamZam', '7', 'Confirmed'],
                    ['MG-1103', 'Mr. Tariq', 'Hilton Suites', '4', 'Pending'],
                    ['MG-1104', 'Group C', 'Anwar Al Madinah', '6', 'Active'],
                ],
            ],
            'quotations' => [
                'title' => 'Quotations',
                'columns' => ['Quote No', 'Client', 'Package', 'Amount', 'Status'],
                'rows' => [
                    ['QT-3301', 'Al Noor Travels', 'Umrah 7N', '185,000', 'Sent'],
                    ['QT-3302', 'Hajj Group PK', 'Haj 14N', '420,000', 'Draft'],
                    ['QT-3303', 'Ms. Sana', 'Umrah 10N', '245,000', 'Accepted'],
                    ['QT-3304', 'Family Iqbal', 'Madinah 5N', '98,000', 'Expired'],
                ],
            ],
            'hotel-vouchers' => [
                'title' => 'Hotel Vouchers',
                'columns' => ['Voucher No', 'Guest', 'Hotel', 'Nights', 'Status'],
                'rows' => [
                    ['HV-2201', 'Group A', 'Makarem Ajyad', '5', 'Draft'],
                    ['HV-2202', 'Group B', 'Pullman ZamZam', '7', 'Issued'],
                    ['HV-2203', 'Mr. Tariq', 'Hilton Suites', '4', 'Draft'],
                    ['HV-2204', 'Ms. Sana', 'Anwar Al Madinah', '3', 'Issued'],
                ],
            ],
            'hotel-allotement-system' => [
                'title' => 'Hotel Allotement System',
                'columns' => ['Hotel', 'City', 'Rooms', 'Available', 'Status'],
                'rows' => [
                    ['Makarem Ajyad', 'Makkah', '40', '12', 'Open'],
                    ['Pullman ZamZam', 'Makkah', '25', '3', 'Limited'],
                    ['Anwar Al Madinah', 'Madinah', '30', '18', 'Open'],
                    ['Hilton Suites', 'Makkah', '20', '0', 'Full'],
                ],
            ],
            'umrah-package' => [
                'title' => 'Umrah Package',
                'columns' => ['Package', 'Nights', 'Hotel', 'Price', 'Status'],
                'rows' => [
                    ['Economy 7N', '7', 'Dar Al Eiman', '95,000', 'Open'],
                    ['Standard 10N', '10', 'Makarem Ajyad', '165,000', 'Open'],
                    ['Premium 14N', '14', 'Pullman ZamZam', '285,000', 'Limited'],
                    ['Family 10N', '10', 'Hilton Suites', '210,000', 'Open'],
                ],
            ],
            'umrah-documents' => [
                'title' => 'Umrah Documents',
                'columns' => ['Doc No', 'Passenger', 'Type', 'Submitted', 'Status'],
                'rows' => [
                    ['UD-801', 'Ahmed Khan', 'Passport Copy', '20 Aug 2026', 'Verified'],
                    ['UD-802', 'Sara Ali', 'Visa Form', '21 Aug 2026', 'Pending'],
                    ['UD-803', 'Bilal Hussain', 'Photo', '22 Aug 2026', 'Verified'],
                    ['UD-804', 'Fatima Noor', 'Mahram Letter', '23 Aug 2026', 'Missing'],
                ],
            ],
            'monthly-hotel-bookings-rates' => [
                'title' => 'Voucher Records',
                'columns' => ['Voucher No', 'Guest', 'Check-in', 'Agent', 'Status'],
                'rows' => [
                    ['HV-2180', 'Group C', '01 Sep 2026', 'Ayesha', 'Used'],
                    ['HV-2181', 'Ms. Sana', '03 Sep 2026', 'Omar', 'Active'],
                    ['HV-2182', 'Group D', '05 Sep 2026', 'Ayesha', 'Cancelled'],
                    ['HV-2183', 'Mr. Tariq', '08 Sep 2026', 'Omar', 'Active'],
                ],
            ],
            'insurance' => [
                'title' => 'Insurance',
                'columns' => ['Policy No', 'Passenger', 'Cover', 'Premium', 'Status'],
                'rows' => [
                    ['INS-401', 'Ahmed Khan', 'Travel + Medical', '4,500', 'Active'],
                    ['INS-402', 'Sara Ali', 'Travel', '2,800', 'Active'],
                    ['INS-403', 'Group B', 'Group Cover', '18,000', 'Pending'],
                    ['INS-404', 'Fatima Noor', 'Travel + Medical', '4,500', 'Expired'],
                ],
            ],
            'haj-packages' => [
                'title' => 'Haj Packages',
                'columns' => ['Package', 'Duration', 'Camp', 'Price', 'Status'],
                'rows' => [
                    ['Shifting Haj', '21 days', 'Aziziyah', '650,000', 'Open'],
                    ['Maktab 1', '25 days', 'Mina', '890,000', 'Limited'],
                    ['VIP Haj', '18 days', 'Clock Tower', '1,250,000', 'Open'],
                    ['Economy Haj', '28 days', 'Aziziyah', '480,000', 'Waitlist'],
                ],
            ],
        ],
    ],

    'study-visa' => [
        'stats' => [
            ['label' => 'Open Cases', 'value' => '73', 'hint' => '12 due soon', 'tone' => 'blue'],
            ['label' => 'Approved', 'value' => '210', 'hint' => '+18 this month', 'tone' => 'green'],
            ['label' => 'Rejected', 'value' => '18', 'hint' => '2.4% rate', 'tone' => 'red'],
            ['label' => 'Students', 'value' => '145', 'hint' => 'Active file', 'tone' => 'navy'],
        ],
        'trend' => [
            'title' => 'Weekly case movement',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [9, 14, 11, 17, 15, 6, 8],
        ],
        'share' => [
            'title' => 'Case outcomes',
            'items' => [
                ['label' => 'Approved', 'value' => 54, 'tone' => 'green'],
                ['label' => 'Open', 'value' => 28, 'tone' => 'blue'],
                ['label' => 'Students', 'value' => 12, 'tone' => 'navy'],
                ['label' => 'Rejected', 'value' => 6, 'tone' => 'red'],
            ],
        ],
        'tables' => [
            'new-consultation' => [
                'title' => 'New Consultation',
                'columns' => ['Case ID', 'Student', 'Country', 'Date', 'Status'],
                'rows' => [
                    ['SV-501', 'Ali Raza', 'UK', '27 Aug 2026', 'New'],
                    ['SV-502', 'Mariam Khan', 'Canada', '27 Aug 2026', 'Scheduled'],
                    ['SV-503', 'Hassan Ali', 'Australia', '28 Aug 2026', 'New'],
                ],
            ],
            'visa-cases' => [
                'title' => 'Visa Cases',
                'columns' => ['Case ID', 'Student', 'Type', 'Updated', 'Status'],
                'rows' => [
                    ['SV-480', 'Noor Fatima', 'Student', '20 Aug 2026', 'In Review'],
                    ['SV-481', 'Usman Ghani', 'Visit', '21 Aug 2026', 'Approved'],
                    ['SV-482', 'Sana Iqbal', 'Student', '22 Aug 2026', 'Rejected'],
                ],
            ],
            'student-records' => [
                'title' => 'Student Records',
                'columns' => ['Student', 'Passport', 'University', 'Intake', 'Status'],
                'rows' => [
                    ['Ali Raza', 'AB1234567', 'UCL', 'Sep 2026', 'Active'],
                    ['Mariam Khan', 'CD7654321', 'Toronto', 'Jan 2027', 'Pending'],
                    ['Hassan Ali', 'EF9988776', 'Melbourne', 'Feb 2027', 'Active'],
                ],
            ],
        ],
    ],

    'travel-tourism' => [
        'stats' => [
            ['label' => 'Visit Visas', 'value' => '156', 'hint' => '+9 this week', 'tone' => 'blue'],
            ['label' => 'Packages Sold', 'value' => '84', 'hint' => 'This month', 'tone' => 'green'],
            ['label' => 'Pending', 'value' => '23', 'hint' => 'Awaiting docs', 'tone' => 'amber'],
            ['label' => 'Rejected', 'value' => '7', 'hint' => '4.3% rate', 'tone' => 'red'],
        ],
        'trend' => [
            'title' => 'Weekly booking volume',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [12, 18, 14, 22, 19, 9, 11],
        ],
        'share' => [
            'title' => 'Product mix',
            'items' => [
                ['label' => 'Visit Visa', 'value' => 48, 'tone' => 'blue'],
                ['label' => 'Packages', 'value' => 32, 'tone' => 'green'],
                ['label' => 'Pending', 'value' => 14, 'tone' => 'amber'],
                ['label' => 'Rejected', 'value' => 6, 'tone' => 'red'],
            ],
        ],
        'tables' => [
            'visit-visa' => [
                'title' => 'Visit Visa',
                'columns' => ['Case ID', 'Applicant', 'Country', 'Submitted', 'Status'],
                'rows' => [
                    ['VV-2101', 'Imran Sheikh', 'UAE', '22 Aug 2026', 'In Review'],
                    ['VV-2102', 'Hira Naveed', 'UK', '23 Aug 2026', 'Approved'],
                    ['VV-2103', 'Kamran Malik', 'Turkey', '24 Aug 2026', 'Pending'],
                    ['VV-2104', 'Ayesha Rauf', 'Malaysia', '25 Aug 2026', 'Rejected'],
                ],
            ],
            'packages' => [
                'title' => 'Packages',
                'columns' => ['Package', 'Destination', 'Days', 'Price', 'Status'],
                'rows' => [
                    ['Dubai Explorer', 'UAE', '5', '125,000', 'Open'],
                    ['Istanbul City', 'Turkey', '7', '165,000', 'Open'],
                    ['Malaysia Family', 'Malaysia', '8', '198,000', 'Limited'],
                    ['UK Highlights', 'United Kingdom', '10', '385,000', 'Open'],
                ],
            ],
        ],
    ],

    'study-visit-visa' => [
        'stats' => [
            ['label' => 'Active Clients', 'value' => '128', 'hint' => '+11 this month', 'tone' => 'blue'],
            ['label' => 'Consultations', 'value' => '64', 'hint' => 'This week', 'tone' => 'navy'],
            ['label' => 'Converted', 'value' => '41', 'hint' => '64% close rate', 'tone' => 'green'],
            ['label' => 'Follow-ups', 'value' => '19', 'hint' => 'Due this week', 'tone' => 'amber'],
        ],
        'trend' => [
            'title' => 'Weekly consultations',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [8, 11, 9, 14, 12, 5, 7],
        ],
        'share' => [
            'title' => 'Service mix',
            'items' => [
                ['label' => 'Visit Visa', 'value' => 34, 'tone' => 'blue'],
                ['label' => 'Student', 'value' => 28, 'tone' => 'navy'],
                ['label' => 'Group Tour', 'value' => 22, 'tone' => 'green'],
                ['label' => 'Follow-ups', 'value' => 16, 'tone' => 'amber'],
            ],
        ],
        'tables' => [
            'visit-visa-consultancy' => [
                'title' => 'Visit Visa Consultancy',
                'columns' => ['Case ID', 'Client', 'Country', 'Consultant', 'Status'],
                'rows' => [
                    ['VC-601', 'Raza Ahmed', 'Schengen', 'Ayesha', 'In Progress'],
                    ['VC-602', 'Nadia Shah', 'UK', 'Omar', 'Scheduled'],
                    ['VC-603', 'Tariq Mehmood', 'USA', 'Ayesha', 'Documents Due'],
                    ['VC-604', 'Sana Iqbal', 'Canada', 'Omar', 'Closed'],
                ],
            ],
            'group-tour-consultancy' => [
                'title' => 'Group Tour Consultancy',
                'columns' => ['Tour ID', 'Group', 'Destination', 'Pax', 'Status'],
                'rows' => [
                    ['GT-301', 'Corporate IT', 'Dubai', '18', 'Quoted'],
                    ['GT-302', 'Family Club', 'Turkey', '24', 'Confirmed'],
                    ['GT-303', 'School Trip', 'Malaysia', '32', 'Planning'],
                    ['GT-304', 'Ladies Group', 'Istanbul', '14', 'Follow-up'],
                ],
            ],
            'student-consultancy' => [
                'title' => 'Student Consultancy',
                'columns' => ['Case ID', 'Student', 'Country', 'Intake', 'Status'],
                'rows' => [
                    ['SC-701', 'Ali Raza', 'UK', 'Sep 2026', 'Counselling'],
                    ['SC-702', 'Mariam Khan', 'Canada', 'Jan 2027', 'Offer Letter'],
                    ['SC-703', 'Hassan Ali', 'Australia', 'Feb 2027', 'Visa Filed'],
                    ['SC-704', 'Noor Fatima', 'Germany', 'Oct 2026', 'New'],
                ],
            ],
            'client-details' => [
                'title' => 'Client Details',
                'columns' => ['Client ID', 'Name', 'Phone', 'Service', 'Status'],
                'rows' => [
                    ['CL-901', 'Raza Ahmed', '0300-1112233', 'Visit Visa', 'Active'],
                    ['CL-902', 'Nadia Shah', '0321-4455667', 'Student', 'Active'],
                    ['CL-903', 'Tariq Mehmood', '0333-7788990', 'Group Tour', 'Lead'],
                    ['CL-904', 'Sana Iqbal', '0345-1122334', 'Visit Visa', 'Closed'],
                ],
            ],
        ],
    ],

    'accounts' => [
        'stats' => [
            ['label' => 'Invoices', 'value' => '492', 'hint' => 'This month', 'tone' => 'blue'],
            ['label' => 'Paid', 'value' => '401', 'hint' => '81% cleared', 'tone' => 'green'],
            ['label' => 'Overdue', 'value' => '27', 'hint' => 'Follow up', 'tone' => 'red'],
            ['label' => 'Pending', 'value' => '64', 'hint' => 'In process', 'tone' => 'amber'],
        ],
        'trend' => [
            'title' => 'Weekly invoice flow',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [24, 31, 28, 40, 36, 15, 21],
        ],
        'share' => [
            'title' => 'Payment status',
            'items' => [
                ['label' => 'Paid', 'value' => 62, 'tone' => 'green'],
                ['label' => 'Pending', 'value' => 20, 'tone' => 'amber'],
                ['label' => 'Invoices', 'value' => 12, 'tone' => 'blue'],
                ['label' => 'Overdue', 'value' => 6, 'tone' => 'red'],
            ],
        ],
        'tables' => [
            'create-invoice' => [
                'title' => 'Create Invoice',
                'columns' => ['Invoice No', 'Client', 'Amount', 'Date', 'Status'],
                'rows' => [
                    ['INV-9001', 'Travel Co', '85,000', '27 Aug 2026', 'Draft'],
                    ['INV-9002', 'Umrah Group', '210,000', '27 Aug 2026', 'Ready'],
                    ['INV-9003', 'Visa Desk', '32,500', '28 Aug 2026', 'Draft'],
                ],
            ],
            'payments' => [
                'title' => 'Payments',
                'columns' => ['Receipt', 'Client', 'Amount', 'Method', 'Status'],
                'rows' => [
                    ['RC-4401', 'Travel Co', '85,000', 'Bank', 'Received'],
                    ['RC-4402', 'Umrah Group', '100,000', 'Cash', 'Partial'],
                    ['RC-4403', 'Visa Desk', '32,500', 'Card', 'Received'],
                ],
            ],
            'reports' => [
                'title' => 'Reports',
                'columns' => ['Report', 'Period', 'Generated', 'By', 'Status'],
                'rows' => [
                    ['Sales Summary', 'Aug 2026', '26 Aug 2026', 'Admin', 'Ready'],
                    ['Receivables', 'Aug 2026', '26 Aug 2026', 'Admin', 'Ready'],
                    ['Expense Log', 'Aug 2026', '25 Aug 2026', 'Finance', 'Ready'],
                ],
            ],
        ],
    ],

    'promotion' => [
        'stats' => [
            ['label' => 'Campaigns', 'value' => '16', 'hint' => '5 live now', 'tone' => 'blue'],
            ['label' => 'Active Ads', 'value' => '38', 'hint' => 'Across channels', 'tone' => 'navy'],
            ['label' => 'Leads', 'value' => '920', 'hint' => '+14% MoM', 'tone' => 'green'],
            ['label' => 'Spend (PKR)', 'value' => '2.4L', 'hint' => 'Budget OK', 'tone' => 'amber'],
        ],
        'trend' => [
            'title' => 'Weekly lead trend',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [55, 62, 48, 78, 71, 44, 59],
        ],
        'share' => [
            'title' => 'Channel mix',
            'items' => [
                ['label' => 'Leads', 'value' => 48, 'tone' => 'green'],
                ['label' => 'Campaigns', 'value' => 22, 'tone' => 'blue'],
                ['label' => 'Ads', 'value' => 20, 'tone' => 'navy'],
                ['label' => 'Spend', 'value' => 10, 'tone' => 'amber'],
            ],
        ],
        'tables' => [
            'campaigns' => [
                'title' => 'Campaigns',
                'columns' => ['Campaign', 'Channel', 'Start', 'Leads', 'Status'],
                'rows' => [
                    ['Umrah Early Bird', 'Facebook', '01 Aug 2026', '320', 'Live'],
                    ['Study UK Fair', 'Google', '10 Aug 2026', '180', 'Live'],
                    ['Haj Package', 'Instagram', '15 Aug 2026', '95', 'Paused'],
                ],
            ],
            'create-ad' => [
                'title' => 'Create Advertisement',
                'columns' => ['Ad Name', 'Platform', 'Budget', 'Created', 'Status'],
                'rows' => [
                    ['Summer Umrah', 'Meta', '45,000', '20 Aug 2026', 'Draft'],
                    ['Visa Promo', 'Google', '30,000', '22 Aug 2026', 'Ready'],
                    ['Hotel Deal', 'TikTok', '18,000', '24 Aug 2026', 'Draft'],
                ],
            ],
            'performance' => [
                'title' => 'Performance',
                'columns' => ['Campaign', 'Impressions', 'Clicks', 'Leads', 'ROI'],
                'rows' => [
                    ['Umrah Early Bird', '120k', '4.2k', '320', '3.1x'],
                    ['Study UK Fair', '80k', '2.8k', '180', '2.4x'],
                    ['Haj Package', '45k', '1.1k', '95', '1.6x'],
                ],
            ],
        ],
    ],

    'attendance' => [
        'stats' => [
            ['label' => 'Present Today', 'value' => '—', 'hint' => 'Coming soon', 'tone' => 'blue'],
            ['label' => 'On Leave', 'value' => '—', 'hint' => 'Coming soon', 'tone' => 'amber'],
            ['label' => 'Pending Leave', 'value' => '—', 'hint' => 'Coming soon', 'tone' => 'navy'],
            ['label' => 'Holidays', 'value' => '—', 'hint' => 'Coming soon', 'tone' => 'red'],
        ],
        'trend' => null,
        'share' => null,
        'tables' => [
            'my-daily-attendance' => [
                'title' => 'My Daily Attendance',
                'columns' => [],
                'rows' => [],
            ],
            'apply-leave' => [
                'title' => 'Apply Leave',
                'columns' => ['Status'],
                'rows' => [
                    ['Leave request form will be added in a later step.'],
                ],
            ],
            'leave-approvals' => [
                'title' => 'Leave Approvals',
                'columns' => ['Status'],
                'rows' => [
                    ['Approve / reject leave will be added in a later step.'],
                ],
            ],
            'office-settings' => [
                'title' => 'Office Settings',
                'columns' => ['Status'],
                'rows' => [
                    ['Office hours and IP settings will be added in a later step.'],
                ],
            ],
            'holidays' => [
                'title' => 'Holidays',
                'columns' => ['Status'],
                'rows' => [
                    ['Holiday list will be added in a later step.'],
                ],
            ],
        ],
    ],

    'settings' => [
        'stats' => [
            // values filled from DB in DashboardController
            ['label' => 'Total Users', 'value' => '0', 'hint' => 'All accounts', 'tone' => 'blue'],
            ['label' => 'Staff', 'value' => '0', 'hint' => 'Limited access', 'tone' => 'amber'],
            ['label' => 'Admins', 'value' => '0', 'hint' => 'Full access', 'tone' => 'navy'],
        ],
        'trend' => null,
        'share' => null,
        'tables' => [],
    ],

];
