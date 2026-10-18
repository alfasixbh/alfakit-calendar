<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar;

use AlfaSix\AlfakitCalendar\Support\CalendarConfig;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * One calendar event, returned from `CalendarWidget::fetchEvents()`.
 *
 * ```php
 * Event::make($meeting)
 *     ->title($meeting->subject)
 *     ->start($meeting->starts_at)
 *     ->end($meeting->ends_at)
 *     ->backgroundColor('#f97316');
 * ```
 *
 * The id is the record key the widget's actions, `onEventDrop()` and `onEventResize()`
 * receive. Dates may be `DateTimeInterface` values (shown in the calendar's timezone) or
 * strings sent as they are.
 */
class Event
{
    protected int|string|null $id = null;

    protected string $title = '';

    protected DateTimeInterface|string|null $start = null;

    protected DateTimeInterface|string|null $end = null;

    protected bool $allDay = false;

    protected ?string $url = null;

    protected bool $shouldOpenUrlInNewTab = false;

    protected ?string $backgroundColor = null;

    protected ?string $borderColor = null;

    protected ?string $textColor = null;

    protected ?bool $editable = null;

    protected ?string $groupId = null;

    /** @var array<int, string> */
    protected array $classNames = [];

    /** @var array<string, mixed> */
    protected array $extendedProps = [];

    final public function __construct(int|string|Model|null $id = null)
    {
        $this->id($id);
    }

    /**
     * @param  int|string|Model|null  $id  A key, or a model whose key is used.
     */
    public static function make(int|string|Model|null $id = null): static
    {
        return new static($id);
    }

    public function id(int|string|Model|null $id): static
    {
        $key = $id instanceof Model ? $id->getKey() : $id;
        $this->id = is_int($key) || is_string($key) ? $key : null;

        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function start(DateTimeInterface|string|null $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function end(DateTimeInterface|string|null $end): static
    {
        $this->end = $end;

        return $this;
    }

    public function allDay(bool $condition = true): static
    {
        $this->allDay = $condition;

        return $this;
    }

    /**
     * Clicking the event visits this URL instead of running the event click action.
     */
    public function url(?string $url, bool $shouldOpenInNewTab = false): static
    {
        $this->url = $url;
        $this->shouldOpenUrlInNewTab = $shouldOpenInNewTab;

        return $this;
    }

    public function backgroundColor(?string $color): static
    {
        $this->backgroundColor = $color;

        return $this;
    }

    public function borderColor(?string $color): static
    {
        $this->borderColor = $color;

        return $this;
    }

    public function textColor(?string $color): static
    {
        $this->textColor = $color;

        return $this;
    }

    /**
     * Allow or forbid dragging and resizing this event (overrides the widget).
     */
    public function editable(?bool $condition = true): static
    {
        $this->editable = $condition;

        return $this;
    }

    public function groupId(?string $groupId): static
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @param  array<int, string>  $classNames
     */
    public function classNames(array $classNames): static
    {
        $this->classNames = array_values(array_filter($classNames, is_string(...)));

        return $this;
    }

    /**
     * Extra data sent with the event (JSON values only), e.g. for a custom event renderer.
     *
     * @param  array<string, mixed>  $props
     */
    public function extendedProps(array $props): static
    {
        $this->extendedProps = $props;

        return $this;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    /**
     * The FullCalendar event object, with dates as wall time in `$timezone`.
     *
     * @return array<string, mixed>
     */
    public function toCalendarArray(string $timezone): array
    {
        return array_filter([
            'id' => $this->id === null ? null : (string) $this->id,
            'title' => $this->title,
            'start' => CalendarConfig::format($this->start, $timezone, $this->allDay),
            'end' => CalendarConfig::format($this->end, $timezone, $this->allDay),
            'allDay' => $this->allDay,
            'url' => $this->url === null ? null : \AlfaSix\Alfakit\Support\Url::sanitize($this->url),
            'openUrlInNewTab' => $this->shouldOpenUrlInNewTab ?: null,
            'backgroundColor' => $this->backgroundColor,
            'borderColor' => $this->borderColor,
            'textColor' => $this->textColor,
            'editable' => $this->editable,
            'groupId' => $this->groupId,
            'classNames' => $this->classNames ?: null,
            'extendedProps' => CalendarConfig::json($this->extendedProps) ?: null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * Without a timezone, in the application's timezone.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->toCalendarArray((string) config('app.timezone', 'UTC'));
    }
}
