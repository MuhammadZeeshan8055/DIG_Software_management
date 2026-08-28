<?php

/**
 * Import Ticket Details — form fields & PDF patterns.
 *
 * Edit "form_fields" for booking / passenger info.
 * Edit "flight_segment_fields" for each flight leg column.
 * Edit "patterns" if your PDF layout changes.
 */
return [

    'max_pdf_size_kb' => 5120,

    'form_fields' => [
        'agency_name' => [
            'label' => 'Agency Name',
            'type' => 'text',
            'full' => true,
            'placeholder' => 'e.g. CB6180-DIG TRAVEL AND TOURS PVT LTD RWP',
        ],
        'agency_phone' => [
            'label' => 'Agency Phone',
            'type' => 'text',
            'placeholder' => 'e.g. +923425083003',
        ],
        'booking_reference' => [
            'label' => 'Booking Reference',
            'type' => 'text',
            'placeholder' => 'e.g. CLI_22494-2615610',
        ],
        'agency_pnr' => [
            'label' => 'Agency PNR',
            'type' => 'text',
            'placeholder' => 'e.g. RRGHVZ',
        ],
        'passenger_name' => [
            'label' => 'Traveller Name',
            'type' => 'text',
            'required' => true,
            'full' => true,
            'placeholder' => 'e.g. Mr MUDDASIR IMTIAZ',
        ],
        'frequent_flyer' => [
            'label' => 'Frequent Flyer',
            'type' => 'text',
            'placeholder' => 'e.g. -',
        ],
        'ticket_number' => [
            'label' => 'Ticket Number',
            'type' => 'text',
            'placeholder' => 'e.g. 2352541196199',
        ],
        'document_title' => [
            'label' => 'Document Title',
            'type' => 'text',
            'placeholder' => 'e.g. Itinerary Receipt',
        ],
    ],

    /*
    | Each flight leg in the PDF (supports multiple segments).
    */
    'flight_segment_fields' => [
        'flight_number' => 'Flight',
        'airline' => 'Airline',
        'departure_time' => 'Dep. Time',
        'departure_date' => 'Dep. Date',
        'departure_location' => 'From',
        'arrival_time' => 'Arr. Time',
        'arrival_date' => 'Arr. Date',
        'arrival_location' => 'To',
        'status' => 'Status',
        'class' => 'Class',
        'baggage' => 'Baggage',
        'pnr' => 'PNR',
    ],

    'patterns' => [
        'agency_name' => '/([A-Z0-9]+-DIG TRAVEL AND TOURS PVT LTD RWP)/i',
        'agency_phone' => '/(\+\d{10,15})/',
        'booking_reference' => '/Booking Reference:\s*([A-Z0-9_\-]+(?:\s*-?\s*[0-9]+)?)/i',
        'agency_pnr' => '/Agency PNR:\s*([A-Z0-9]+)/i',
        'passenger_name' => '/TRAVELLER NAME\s+(.+?)\s*\(ADT\)/i',
        'frequent_flyer' => '/FREQUENT FLYER\s+(.+?)\s+TICKET NO\./i',
        'ticket_number' => '/TICKET NO\.\s*(\d+)/i',
        'document_title' => '/(Itinerary Receipt)/i',
    ],

];
