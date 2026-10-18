<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => 'Il campo :attribute non è una data valida.',
        'range_too_long' => 'L’intervallo richiesto è troppo lungo.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Calendario',
        'today' => 'Oggi',
        'month' => 'Mese',
        'week' => 'Settimana',
        'day' => 'Giorno',
        'list' => 'Elenco',
        'all_day' => 'Tutto il giorno',
        'more_events' => 'Altri :count',
        'no_events' => 'Nessun evento da mostrare',
        'previous' => 'Precedente',
        'next' => 'Successivo',
        'loading' => 'Caricamento del calendario',
        'load_failed' => 'Impossibile caricare gli eventi.',
        'move_failed' => 'Impossibile salvare la modifica.',
        'view_hint' => 'Vista :view',
        'go_to_date' => 'Vai a :date',
        'more_events_hint' => 'Mostra altri :count eventi',
        'time' => 'Ora',
        'event' => 'Evento',
        'week_number_short' => 'Sett',
    ],

];
