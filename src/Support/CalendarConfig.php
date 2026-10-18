<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Helpers shared by the calendar widget and plugin: the FullCalendar option allow-list and
 * the conversion between the calendar's wall time and the application timezone.
 *
 * The renderers run FullCalendar in UTC mode and the server sends "wall time" strings
 * (`2026-10-13T09:30:00`, no offset) in the calendar's timezone, so FullCalendar shows them
 * exactly as given without a timezone plugin. Dates coming back from the browser are wall
 * times in that timezone too.
 */
final class CalendarConfig
{
    /** The views a calendar may offer. */
    public const VIEWS = ['dayGridMonth', 'dayGridWeek', 'dayGridDay', 'timeGridWeek', 'timeGridDay', 'listDay', 'listWeek', 'listMonth', 'listYear'];

    /** Options the widget sets itself; a `config()` entry can't replace them. */
    private const RESERVED = [
        'plugins', 'events', 'eventSources', 'initialEvents', 'eventClick', 'eventDrop', 'eventResize',
        'select', 'unselect', 'dateClick', 'datesSet', 'loading', 'eventAdd', 'eventChange', 'eventRemove',
        'eventsSet', 'eventDragStart', 'eventDragStop', 'eventResizeStart', 'eventResizeStop',
        'eventReceive', 'eventLeave', 'drop', 'timeZone', 'direction', 'locale', 'locales',
        'editable', 'selectable', 'initialView', 'headerToolbar', 'footerToolbar', 'buttonText',
        'customButtons', 'windowResize', 'viewDidMount', 'viewWillUnmount', 'navLinkDayClick',
        'navLinkWeekClick', 'selectAllow', 'eventAllow', 'eventDataTransform', 'eventSourceSuccess',
        'eventSourceFailure', 'eventOrder', 'schedulerLicenseKey',
    ];

    /** Longest range the events endpoint answers (a year view plus padding). */
    public const MAX_RANGE_DAYS = 400;

    private const UNSAFE = "\0unsafe";

    /**
     * Keep JSON-safe values only (scalars, null and arrays of them) and drop the options the
     * widget controls, callbacks (`*Content`, `*DidMount`, `*WillUnmount`, `*ClassNames`
     * functions are impossible in JSON anyway) and any key that isn't a plain identifier.
     *
     * @param  array<array-key, mixed>  $config
     * @return array<string, mixed>
     */
    public static function sanitize(array $config): array
    {
        $clean = [];

        foreach ($config as $key => $value) {
            if (! is_string($key) || preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $key) !== 1) {
                continue;
            }

            if (in_array($key, self::RESERVED, true) || preg_match('/(Content|DidMount|WillUnmount|Allow)$/', $key) === 1) {
                continue;
            }

            $safe = self::jsonValue($value);

            if ($safe !== self::UNSAFE) {
                $clean[$key] = $safe;
            }
        }

        return $clean;
    }

    /**
     * The JSON-safe part of an array (scalars, null and nested arrays of them), keys kept.
     *
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    public static function json(array $values): array
    {
        $safe = self::jsonValue($values);

        return is_array($safe) ? $safe : [];
    }

    private static function jsonValue(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        if (is_float($value)) {
            return is_finite($value) ? $value : self::UNSAFE;
        }

        if (is_array($value)) {
            $clean = [];

            foreach ($value as $key => $item) {
                $item = self::jsonValue($item);

                if ($item !== self::UNSAFE) {
                    $clean[$key] = $item;
                }
            }

            return array_is_list($value) ? array_values($clean) : $clean;
        }

        return self::UNSAFE;
    }

    /**
     * Only the known views, in the given order (unknown names are dropped).
     *
     * @param  array<mixed>  $views
     * @return array<int, string>
     */
    public static function views(array $views): array
    {
        return array_values(array_unique(array_filter($views, static fn (mixed $view): bool => is_string($view) && in_array($view, self::VIEWS, true))));
    }

    /**
     * A wall-time string from the browser (`2026-10-13T09:30:00`, `2026-10-13`, an optional
     * `Z`/offset is ignored) read in the calendar's timezone and returned in the
     * application's timezone. Null when it isn't a date.
     */
    public static function parse(mixed $value, string $timezone): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^(\d{4}-\d{2}-\d{2})(?:[T ](\d{2}:\d{2}(?::\d{2})?)(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?$/', trim($value), $matches) !== 1) {
            return null;
        }

        $wall = $matches[1].(isset($matches[2]) ? ' '.$matches[2] : ' 00:00');

        try {
            return CarbonImmutable::parse($wall, $timezone)->setTimezone((string) config('app.timezone', 'UTC'));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * A date for FullCalendar: DateTime values are converted to the calendar's timezone and
     * written as wall time (`Y-m-d` for all-day events); strings are sent as they are.
     */
    public static function format(DateTimeInterface|string|null $value, string $timezone, bool $allDay = false): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        // All-day dates are calendar days, not instants: they keep their own date.
        if ($allDay) {
            return CarbonImmutable::instance($value)->format('Y-m-d');
        }

        return CarbonImmutable::instance($value)->setTimezone($timezone)->format('Y-m-d\TH:i:s');
    }
}
