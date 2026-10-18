<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => ':attribute は有効な日付ではありません。',
        'range_too_long' => '指定された期間が長すぎます。',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => 'カレンダー',
        'today' => '今日',
        'month' => '月',
        'week' => '週',
        'day' => '日',
        'list' => 'リスト',
        'all_day' => '終日',
        'more_events' => '他 :count 件',
        'no_events' => '表示する予定はありません',
        'previous' => '前へ',
        'next' => '次へ',
        'loading' => 'カレンダーを読み込んでいます',
        'load_failed' => '予定を読み込めませんでした。',
        'move_failed' => '変更を保存できませんでした。',
        'view_hint' => ':view 表示',
        'go_to_date' => ':date に移動',
        'more_events_hint' => '他 :count 件の予定を表示',
        'time' => '時間',
        'event' => '予定',
        'week_number_short' => '週',
    ],

];
