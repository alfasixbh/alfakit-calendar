<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => 'Поле :attribute не является корректной датой.',
        'range_too_long' => 'Запрошенный период слишком длинный.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Календарь',
        'today' => 'Сегодня',
        'month' => 'Месяц',
        'week' => 'Неделя',
        'day' => 'День',
        'list' => 'Список',
        'all_day' => 'Весь день',
        'more_events' => 'Ещё :count',
        'no_events' => 'Нет событий для отображения',
        'previous' => 'Назад',
        'next' => 'Вперёд',
        'loading' => 'Загрузка календаря',
        'load_failed' => 'Не удалось загрузить события.',
        'move_failed' => 'Не удалось сохранить изменение.',
        'view_hint' => 'Вид «:view»',
        'go_to_date' => 'Перейти к :date',
        'more_events_hint' => 'Показать ещё событий: :count',
        'time' => 'Время',
        'event' => 'Событие',
        'week_number_short' => 'Н',
    ],

];
