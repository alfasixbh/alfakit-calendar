<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => ':attribute ist kein gültiges Datum.',
        'range_too_long' => 'Der angeforderte Zeitraum ist zu lang.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Kalender',
        'today' => 'Heute',
        'month' => 'Monat',
        'week' => 'Woche',
        'day' => 'Tag',
        'list' => 'Liste',
        'all_day' => 'Ganztägig',
        'more_events' => '+:count weitere',
        'no_events' => 'Keine Termine vorhanden',
        'previous' => 'Zurück',
        'next' => 'Weiter',
        'loading' => 'Kalender wird geladen',
        'load_failed' => 'Die Termine konnten nicht geladen werden.',
        'move_failed' => 'Die Änderung konnte nicht gespeichert werden.',
        'view_hint' => ':view-Ansicht',
        'go_to_date' => 'Zu :date wechseln',
        'more_events_hint' => ':count weitere Termine anzeigen',
        'time' => 'Uhrzeit',
        'event' => 'Termin',
        'week_number_short' => 'KW',
    ],

];
