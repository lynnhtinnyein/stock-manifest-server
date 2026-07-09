<?php

return [
    'patterns' => [
        '_at$',
        '_time$',
        '_date$',
        '^date_',
        '_on$',
    ],

    'fields' => [
        'timestamp',
        'datetime',
        'scheduled',
        'expires',
        'departure',
        'arrival',
        'voyage_start',
        'voyage_end',
        'port_time',
        'eta',
        'etd',
    ],

    'models' => [
        'Booking' => [
            'departure_time',
            'arrival_time',
            'booking_date',
        ],
        'Voyage' => [
            'departure_time',
            'estimated_arrival',
            'actual_arrival',
        ],
        'Port' => [
            'last_visit',
            'next_available',
        ],
    ],

    'excluded' => [
        'password',
        'token',
        'remember_token',
        '_method',
        '_token',
        'api_key',
    ],
];
