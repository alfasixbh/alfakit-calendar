<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => ':attribute is geen geldige datum.',
        'range_too_long' => 'De gevraagde periode is te lang.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Kalender',
        'today' => 'Vandaag',
        'month' => 'Maand',
        'week' => 'Week',
        'day' => 'Dag',
        'list' => 'Lijst',
        'all_day' => 'Hele dag',
        'more_events' => '+:count meer',
        'no_events' => 'Geen afspraken om weer te geven',
        'previous' => 'Vorige',
        'next' => 'Volgende',
        'loading' => 'Kalender wordt geladen',
        'load_failed' => 'De afspraken konden niet worden geladen.',
        'move_failed' => 'De wijziging kon niet worden opgeslagen.',
        'view_hint' => 'Weergave :view',
        'go_to_date' => 'Ga naar :date',
        'more_events_hint' => ':count extra afspraken tonen',
        'time' => 'Tijd',
        'event' => 'Afspraak',
        'week_number_short' => 'W',
    ],

];
