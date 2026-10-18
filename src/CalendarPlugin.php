<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar;

use AlfaSix\Alfakit\Contracts\Plugin;
use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitCalendar\Support\CalendarConfig;

/**
 * Panel-wide defaults for every `CalendarWidget` of a panel.
 *
 * ```php
 * $panel->plugin(
 *     CalendarPlugin::make()
 *         ->timezone('Asia/Bahrain')
 *         ->firstDay(6)
 *         ->editable()
 *         ->config(['dayMaxEvents' => true]),
 * );
 * ```
 *
 * Widgets override any of these with their own methods (`getTimezone()`, `config()`…).
 */
class CalendarPlugin implements Plugin
{
    public const ID = 'calendar';

    protected ?string $timezone = null;

    protected ?string $locale = null;

    protected ?int $firstDay = null;

    protected ?bool $editable = null;

    protected ?bool $selectable = null;

    /** @var array<string, mixed> */
    protected array $config = [];

    public static function make(): static
    {
        return new static;
    }

    /**
     * The plugin registered on the current panel, or null when it isn't.
     */
    public static function get(): ?static
    {
        $panel = alfakit()->getCurrentOrDefaultPanel();

        if (! $panel->hasPlugin(self::ID)) {
            return null;
        }

        $plugin = $panel->getPlugin(self::ID);

        return $plugin instanceof static ? $plugin : null;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        $panel->frontendTranslations(['calendar' => 'alfakit-calendar::calendar.ui']);
    }

    public function boot(Panel $panel): void {}

    /**
     * The timezone events are shown in (defaults to the panel's timezone).
     */
    public function timezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * A BCP 47 locale for dates and week numbers (defaults to the panel's current locale).
     */
    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * 0 = Sunday … 6 = Saturday (defaults to the panel's first day of the week).
     */
    public function firstDay(?int $day): static
    {
        $this->firstDay = $day === null ? null : (($day % 7) + 7) % 7;

        return $this;
    }

    public function getFirstDay(): ?int
    {
        return $this->firstDay;
    }

    /**
     * Allow dragging and resizing events (handled by the widget's `onEventDrop()` and
     * `onEventResize()`).
     */
    public function editable(bool $condition = true): static
    {
        $this->editable = $condition;

        return $this;
    }

    public function isEditable(): ?bool
    {
        return $this->editable;
    }

    /**
     * Allow selecting a date range (opens the widget's date select action). By default a
     * range is selectable when the widget has a date select action.
     */
    public function selectable(bool $condition = true): static
    {
        $this->selectable = $condition;

        return $this;
    }

    public function isSelectable(): ?bool
    {
        return $this->selectable;
    }

    /**
     * Extra FullCalendar options (JSON values only: functions, objects and the options the
     * widget controls are dropped).
     *
     * @param  array<string, mixed>  $config
     */
    public function config(array $config): static
    {
        $this->config = CalendarConfig::sanitize($config);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
