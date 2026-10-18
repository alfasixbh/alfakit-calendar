<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => 'O campo :attribute não é uma data válida.',
        'range_too_long' => 'O período solicitado é longo demais.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Calendário',
        'today' => 'Hoje',
        'month' => 'Mês',
        'week' => 'Semana',
        'day' => 'Dia',
        'list' => 'Lista',
        'all_day' => 'Dia inteiro',
        'more_events' => '+:count mais',
        'no_events' => 'Nenhum evento para exibir',
        'previous' => 'Anterior',
        'next' => 'Próximo',
        'loading' => 'Carregando calendário',
        'load_failed' => 'Não foi possível carregar os eventos.',
        'move_failed' => 'Não foi possível salvar a alteração.',
        'view_hint' => 'Visualização de :view',
        'go_to_date' => 'Ir para :date',
        'more_events_hint' => 'Mostrar mais :count eventos',
        'time' => 'Hora',
        'event' => 'Evento',
        'week_number_short' => 'S',
    ],

];
