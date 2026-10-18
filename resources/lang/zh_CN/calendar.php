<?php

declare(strict_types=1);

return [

    'validation' => [
        'invalid_date' => ':attribute 不是有效的日期。',
        'range_too_long' => '请求的时间范围过长。',
    ],

    // Shared with the renderers as `calendar.*` (see CalendarPlugin::register()).
    'ui' => [
        'label' => '日历',
        'today' => '今天',
        'month' => '月',
        'week' => '周',
        'day' => '日',
        'list' => '列表',
        'all_day' => '全天',
        'more_events' => '还有 :count 项',
        'no_events' => '没有可显示的日程',
        'previous' => '上一页',
        'next' => '下一页',
        'loading' => '正在加载日历',
        'load_failed' => '无法加载日程。',
        'move_failed' => '无法保存更改。',
        'view_hint' => ':view视图',
        'go_to_date' => '转到 :date',
        'more_events_hint' => '再显示 :count 个事件',
        'time' => '时间',
        'event' => '事件',
        'week_number_short' => '周',
    ],

];
