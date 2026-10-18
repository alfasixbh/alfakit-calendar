<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => 'الحقل :attribute ليس تاريخًا صالحًا.',
        'range_too_long' => 'النطاق المطلوب طويل جدًا.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'التقويم',
        'today' => 'اليوم',
        'month' => 'شهر',
        'week' => 'أسبوع',
        'day' => 'يوم',
        'list' => 'قائمة',
        'all_day' => 'طوال اليوم',
        'more_events' => '+:count أخرى',
        'no_events' => 'لا توجد أحداث لعرضها',
        'previous' => 'السابق',
        'next' => 'التالي',
        'loading' => 'جارٍ تحميل التقويم',
        'load_failed' => 'تعذر تحميل الأحداث.',
        'move_failed' => 'تعذر حفظ التغيير.',
        'view_hint' => 'عرض :view',
        'go_to_date' => 'الانتقال إلى :date',
        'more_events_hint' => 'عرض :count أحداث أخرى',
        'time' => 'الوقت',
        'event' => 'حدث',
        'week_number_short' => 'أ',
    ],

];
