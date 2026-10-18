<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => 'Le champ :attribute n’est pas une date valide.',
        'range_too_long' => 'La période demandée est trop longue.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Calendrier',
        'today' => 'Aujourd’hui',
        'month' => 'Mois',
        'week' => 'Semaine',
        'day' => 'Jour',
        'list' => 'Liste',
        'all_day' => 'Toute la journée',
        'more_events' => '+:count de plus',
        'no_events' => 'Aucun événement à afficher',
        'previous' => 'Précédent',
        'next' => 'Suivant',
        'loading' => 'Chargement du calendrier',
        'load_failed' => 'Les événements n’ont pas pu être chargés.',
        'move_failed' => 'La modification n’a pas pu être enregistrée.',
        'view_hint' => 'Vue :view',
        'go_to_date' => 'Aller au :date',
        'more_events_hint' => 'Afficher :count événements de plus',
        'time' => 'Heure',
        'event' => 'Événement',
        'week_number_short' => 'S',
    ],

];
