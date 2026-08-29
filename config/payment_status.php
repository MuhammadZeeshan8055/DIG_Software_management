<?php

/**
 * Payment / ledger options for ticket import demo.
 * No database — session only until you add a migration.
 */
return [

    'default' => 'PENDING',

    'currency' => 'PKR',

    'options' => [
        'PAID' => 'Paid',
        'PENDING' => 'Pending',
        'HALF_RECEIVE' => 'Half Receive',
    ],

];
