<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => ':attribute geçerli bir tarih değil.',
        'range_too_long' => 'İstenen aralık çok uzun.',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'Takvim',
        'today' => 'Bugün',
        'month' => 'Ay',
        'week' => 'Hafta',
        'day' => 'Gün',
        'list' => 'Liste',
        'all_day' => 'Tüm gün',
        'more_events' => '+:count daha',
        'no_events' => 'Gösterilecek etkinlik yok',
        'previous' => 'Önceki',
        'next' => 'Sonraki',
        'loading' => 'Takvim yükleniyor',
        'load_failed' => 'Etkinlikler yüklenemedi.',
        'move_failed' => 'Değişiklik kaydedilemedi.',
        'view_hint' => ':view görünümü',
        'go_to_date' => ':date tarihine git',
        'more_events_hint' => ':count etkinlik daha göster',
        'time' => 'Saat',
        'event' => 'Etkinlik',
        'week_number_short' => 'H',
    ],

];
