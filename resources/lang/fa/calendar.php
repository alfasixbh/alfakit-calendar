<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => ':attribute تاریخ معتبری نیست.',
        'range_too_long' => 'بازهٔ درخواستی بیش از حد طولانی است.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'تقویم',
        'today' => 'امروز',
        'month' => 'ماه',
        'week' => 'هفته',
        'day' => 'روز',
        'list' => 'فهرست',
        'all_day' => 'تمام روز',
        'more_events' => '+:count مورد دیگر',
        'no_events' => 'رویدادی برای نمایش وجود ندارد',
        'previous' => 'قبلی',
        'next' => 'بعدی',
        'loading' => 'در حال بارگذاری تقویم',
        'load_failed' => 'رویدادها بارگذاری نشدند.',
        'move_failed' => 'تغییر ذخیره نشد.',
        'view_hint' => 'نمای :view',
        'go_to_date' => 'رفتن به :date',
        'more_events_hint' => 'نمایش :count رویداد دیگر',
        'time' => 'زمان',
        'event' => 'رویداد',
        'week_number_short' => 'ه',
    ],

];
