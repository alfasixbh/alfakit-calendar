<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => 'El campo :attribute no es una fecha válida.',
        'range_too_long' => 'El rango solicitado es demasiado largo.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Calendario',
        'today' => 'Hoy',
        'month' => 'Mes',
        'week' => 'Semana',
        'day' => 'Día',
        'list' => 'Lista',
        'all_day' => 'Todo el día',
        'more_events' => '+:count más',
        'no_events' => 'No hay eventos que mostrar',
        'previous' => 'Anterior',
        'next' => 'Siguiente',
        'loading' => 'Cargando calendario',
        'load_failed' => 'No se pudieron cargar los eventos.',
        'move_failed' => 'No se pudo guardar el cambio.',
        'view_hint' => 'Vista de :view',
        'go_to_date' => 'Ir a :date',
        'more_events_hint' => 'Mostrar :count eventos más',
        'time' => 'Hora',
        'event' => 'Evento',
        'week_number_short' => 'S',
    ],

];
