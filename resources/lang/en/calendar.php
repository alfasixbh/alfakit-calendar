<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => 'The :attribute is not a valid date.',
        'range_too_long' => 'The requested range is too long.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Calendar',
        'today' => 'Today',
        'month' => 'Month',
        'week' => 'Week',
        'day' => 'Day',
        'list' => 'List',
        'all_day' => 'All day',
        'more_events' => '+:count more',
        'no_events' => 'No events to display',
        'previous' => 'Previous',
        'next' => 'Next',
        'loading' => 'Loading calendar',
        'load_failed' => 'The events could not be loaded.',
        'move_failed' => 'The change could not be saved.',
        'view_hint' => ':view view',
        'go_to_date' => 'Go to :date',
        'more_events_hint' => 'Show :count more events',
        'time' => 'Time',
        'event' => 'Event',
        'week_number_short' => 'W',
    ],

];
