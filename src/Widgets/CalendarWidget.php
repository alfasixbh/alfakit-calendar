<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar\Widgets;

use AlfaSix\Alfakit\Actions\Action;
use AlfaSix\Alfakit\Actions\ActionGroup;
use AlfaSix\Alfakit\Actions\CreateAction;
use AlfaSix\Alfakit\Actions\DeleteAction;
use AlfaSix\Alfakit\Actions\EditAction;
use AlfaSix\Alfakit\Actions\ViewAction;
use AlfaSix\Alfakit\Contracts\HasResource;
use AlfaSix\Alfakit\Http\Controllers\Concerns\PullsFlashedNotifications;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\Alfakit\Support\Authorization;
use AlfaSix\Alfakit\Widgets\Widget;
use AlfaSix\AlfakitCalendar\CalendarPlugin;
use AlfaSix\AlfakitCalendar\Event;
use AlfaSix\AlfakitCalendar\Support\CalendarConfig;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * A FullCalendar widget. Return the events of the visible range from `fetchEvents()`:
 *
 * ```php
 * class MeetingsCalendar extends CalendarWidget
 * {
 *     protected static ?string $model = Meeting::class;
 *
 *     public function fetchEvents(array $info): array
 *     {
 *         return Meeting::query()
 *             ->where('starts_at', '<', $info['end'])
 *             ->where('ends_at', '>', $info['start'])
 *             ->get()
 *             ->map(fn (Meeting $meeting) => Event::make($meeting)
 *                 ->title($meeting->subject)
 *                 ->start($meeting->starts_at)
 *                 ->end($meeting->ends_at))
 *             ->all();
 *     }
 * }
 * ```
 *
 * Everything runs through the action endpoint as `widget:<id>`: the events are fetched by
 * a JSON action, clicking an event opens `getEventClickAction()` for the event's record,
 * selecting a range opens `getDateSelectAction()`, and dragging or resizing an event calls
 * `onEventDrop()` / `onEventResize()` (the browser reverts the change unless they return
 * true). The context token, panel access, tenant and the action's `authorize()` are
 * checked on every request.
 */
abstract class CalendarWidget extends Widget implements HasResource
{
    use PullsFlashedNotifications;

    /** Internal JSON actions the renderer calls. */
    public const FETCH_EVENTS_ACTION = 'calendarFetchEvents';

    public const EVENT_DROP_ACTION = 'calendarEventDrop';

    public const EVENT_RESIZE_ACTION = 'calendarEventResize';

    /** The renderer sends its dates under this argument: `{calendar: {start, end, allDay…}}`. */
    public const ARGUMENTS_KEY = 'calendar';

    /** The renderer component (`widget:calendar` in every renderer's registry). */
    protected static ?string $component = 'widget:calendar';

    /** @var class-string<Model>|null */
    protected static ?string $model = null;

    /** @var class-string<resource>|null */
    protected static ?string $resource = null;

    /**
     * Attributes the default `onEventDrop()` / `onEventResize()` write the new dates to.
     * Without `$startAttribute` dragging is reverted unless you override those methods.
     */
    protected static ?string $startAttribute = null;

    protected static ?string $endAttribute = null;

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    /** @var array<int, string> */
    protected array $views = ['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'];

    protected ?string $initialView = null;

    /** @var array<string, Action>|null */
    private ?array $cachedActions = null;

    /**
     * The events of the visible range. `$info` holds `start` and `end` (CarbonImmutable,
     * application timezone, end exclusive) and the calendar's `timezone`.
     *
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, timezone: string}  $info
     * @return array<Event|array<string, mixed>>
     */
    abstract public function fetchEvents(array $info): array;

    public function getType(): string
    {
        return 'Calendar';
    }

    // ---------------------------------------------------------------- Model and resource

    public function getResourceClass(): ?string
    {
        return static::$resource;
    }

    /**
     * @return class-string<Model>|null
     */
    public function getModel(): ?string
    {
        if (static::$model !== null) {
            return static::$model;
        }

        $resource = static::$resource;

        return $resource !== null ? $resource::getModel() : null;
    }

    /**
     * The query event records are resolved from: the resource's (tenant-scoped) query, or
     * the model's own query scoped to the current tenant.
     *
     * @return Builder<Model>|null
     */
    public function getEloquentQuery(): ?Builder
    {
        $resource = static::$resource;

        if ($resource !== null) {
            return $resource::getEloquentQuery();
        }

        $model = $this->getModel();

        if ($model === null) {
            return null;
        }

        $query = $model::query();
        $panel = alfakit()->getCurrentPanel();
        $tenant = $panel?->getTenant();

        if ($panel !== null && $tenant !== null) {
            $relationship = $panel->getTenantOwnershipRelationshipName();

            if (method_exists($query->getModel(), $relationship)) {
                Resource::scopeEloquentQueryToTenant($query, $tenant);
            }
        }

        return $query;
    }

    /**
     * The record behind an event id (null for calendars without a model).
     */
    public function resolveEventRecord(int|string $key): ?Model
    {
        $query = $this->getEloquentQuery();

        if ($query === null) {
            return null;
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($query->getModel()), true)) {
            $query->withTrashed();
        }

        return $query->whereKey($key)->first();
    }

    // ---------------------------------------------------------------- Configuration

    protected function getHeading(): ?string
    {
        return $this->heading;
    }

    /**
     * The views offered in the toolbar: `dayGridMonth`, `dayGridWeek`, `dayGridDay`,
     * `timeGridWeek`, `timeGridDay`, `listDay`, `listWeek`, `listMonth`, `listYear`.
     *
     * @return array<int, string>
     */
    protected function getViews(): array
    {
        return $this->views;
    }

    protected function getInitialView(): ?string
    {
        return $this->initialView;
    }

    public function getTimezone(): string
    {
        return CalendarPlugin::get()?->getTimezone() ?? alfakit()->getTimezone();
    }

    /**
     * A BCP 47 locale; null follows the panel's current locale.
     */
    protected function getLocale(): ?string
    {
        return CalendarPlugin::get()?->getLocale();
    }

    /**
     * 0 = Sunday … 6 = Saturday; null follows the panel.
     */
    protected function getFirstDay(): ?int
    {
        return CalendarPlugin::get()?->getFirstDay();
    }

    /**
     * Whether events can be dragged and resized.
     */
    public function isEditable(): bool
    {
        return CalendarPlugin::get()?->isEditable() ?? false;
    }

    /**
     * Whether a date range can be selected (it opens `getDateSelectAction()`).
     */
    public function isSelectable(): bool
    {
        return (CalendarPlugin::get()?->isSelectable() ?? true) && $this->getDateSelectActionName() !== null;
    }

    /**
     * Extra FullCalendar options for this widget, merged over the plugin's `config()`.
     * Only JSON values are sent; callbacks and the options the widget controls are dropped.
     *
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return CalendarConfig::sanitize([...(CalendarPlugin::get()?->getConfig() ?? []), ...$this->config()]);
    }

    // ---------------------------------------------------------------- Actions

    /**
     * The fields of the default create, edit and view actions.
     *
     * @return array<\AlfaSix\Alfakit\Schemas\Components\Component>
     */
    public function getFormSchema(): array
    {
        return [];
    }

    /**
     * Buttons above the calendar. By default a create action when the widget has a model
     * and a form.
     *
     * @return array<Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return $this->hasDefaultActions() ? [$this->createAction()] : [];
    }

    /**
     * Footer actions of the default event click (view) modal.
     *
     * @return array<Action>
     */
    protected function getModalActions(): array
    {
        return $this->hasDefaultActions() ? [$this->editAction(), $this->deleteAction()] : [];
    }

    /**
     * The action opened when an event without a URL is clicked; it receives the event's
     * record. By default a view modal with edit and delete buttons.
     */
    protected function getEventClickAction(): ?Action
    {
        if (! $this->hasDefaultActions()) {
            return null;
        }

        $modalActions = $this->getModalActions();

        return $this->viewAction()->extraModalFooterActions($modalActions === [] ? null : $modalActions);
    }

    /**
     * The action opened when a date range is selected. Its `$arguments` hold `start`,
     * `end` (CarbonImmutable, application timezone, end exclusive) and `allDay`, e.g. to
     * fill the form with `mountUsing()`. By default the header action named `create`.
     */
    protected function getDateSelectAction(): ?Action
    {
        foreach ($this->getHeaderActions() as $action) {
            foreach ($action instanceof ActionGroup ? $action->getFlatActions() : [$action] as $flat) {
                if ($flat->getName() === 'create') {
                    return $flat;
                }
            }
        }

        return null;
    }

    protected function hasDefaultActions(): bool
    {
        return $this->getModel() !== null && $this->getFormSchema() !== [];
    }

    /**
     * A create action with the widget's model and form. Give actions you open from a range
     * selection their own name when the header also has a `create` action.
     */
    public function createAction(string $name = 'create'): CreateAction
    {
        $model = $this->getModel();

        return CreateAction::make($name)
            ->model($model)
            ->schema(fn (): array => $this->getFormSchema())
            ->mountUsing(function (?Schema $form, array $arguments): void {
                $form?->fill();
                $data = $this->getDateSelectFormData($arguments);

                // A selected range: the defaults, then the selected dates on top.
                if ($form !== null && $data !== []) {
                    $form->fill([...$form->getRawState(), ...$data]);
                }
            });
    }

    public function editAction(string $name = 'edit'): EditAction
    {
        return EditAction::make($name)->model($this->getModel())->schema(fn (): array => $this->getFormSchema());
    }

    public function viewAction(string $name = 'view'): ViewAction
    {
        return ViewAction::make($name)->model($this->getModel())->schema(fn (): array => $this->getFormSchema());
    }

    public function deleteAction(string $name = 'delete'): DeleteAction
    {
        return DeleteAction::make($name)->model($this->getModel());
    }

    /**
     * Form data of the create action opened by a range selection: by default the start
     * and end attributes (`$startAttribute`, `$endAttribute`) when they are set.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    protected function getDateSelectFormData(array $arguments): array
    {
        $data = [];
        $start = $arguments['start'] ?? null;
        $end = $arguments['end'] ?? null;

        if (static::$startAttribute !== null && $start instanceof CarbonImmutable) {
            $data[static::$startAttribute] = $start->format('Y-m-d H:i:s');
        }

        if (static::$endAttribute !== null && $end instanceof CarbonImmutable) {
            $data[static::$endAttribute] = $end->format('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Every action the widget answers, by name (the action endpoint asks `getAction()`).
     *
     * @return array<string, Action>
     */
    protected function getAllActions(): array
    {
        if ($this->cachedActions !== null) {
            return $this->cachedActions;
        }

        $actions = [];

        foreach ($this->getPreparedHeaderActions() as $action) {
            foreach ($action instanceof ActionGroup ? $action->getFlatActions() : [$action] as $flat) {
                $actions[$flat->getName()] = $flat;
            }
        }

        foreach ([$this->getEventClickAction(), $this->getDateSelectAction()] as $action) {
            if ($action !== null) {
                $actions[$action->getName()] ??= $this->prepare($action);
            }
        }

        foreach ($this->makeInternalActions() as $action) {
            $actions[$action->getName()] = $this->prepare($action);
        }

        return $this->cachedActions = $actions;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getPreparedHeaderActions(): array
    {
        $actions = $this->getHeaderActions();

        foreach ($actions as $action) {
            $action->context($this);
        }

        return $actions;
    }

    protected function prepare(Action $action): Action
    {
        return $action->context($this);
    }

    protected function getEventClickActionName(): ?string
    {
        return $this->getEventClickAction()?->getName();
    }

    protected function getDateSelectActionName(): ?string
    {
        return $this->getDateSelectAction()?->getName();
    }

    /**
     * An action by name for the action endpoint (`widget:<id>`). Record actions get the
     * event's record (posted as `record`, resolved within `getEloquentQuery()`), and the
     * renderer's dates are converted to the application timezone.
     */
    public function getAction(string $name): ?Action
    {
        $action = $this->getAllActions()[$name] ?? null;

        if ($action === null) {
            return null;
        }

        $request = request();
        $raw = $request->input('arguments.'.self::ARGUMENTS_KEY);

        if (is_array($raw)) {
            $action->arguments([...$action->getArguments(), ...$this->normalizeArguments($raw)]);
        }

        $key = $request->input('record');
        $isRecordAction = in_array($name, [self::EVENT_DROP_ACTION, self::EVENT_RESIZE_ACTION, $this->getEventClickActionName()], true);

        if ($isRecordAction && (is_string($key) || is_int($key)) && $key !== '') {
            $record = $this->resolveEventRecord($key);

            abort_if($this->getModel() !== null && $record === null, 404);

            $action->record($record);
            $action->arguments([...$action->getArguments(), 'eventId' => (string) $key]);
        }

        return $action;
    }

    /**
     * @param  array<mixed>  $raw
     * @return array<string, mixed>
     */
    protected function normalizeArguments(array $raw): array
    {
        $timezone = $this->getTimezone();
        $allDay = filter_var($raw['allDay'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $oldAllDay = filter_var($raw['oldAllDay'] ?? $allDay, FILTER_VALIDATE_BOOLEAN);

        return [
            'start' => $this->parseDate($raw['start'] ?? null, $timezone, $allDay),
            'end' => $this->parseDate($raw['end'] ?? null, $timezone, $allDay),
            'allDay' => $allDay,
            'oldStart' => $this->parseDate($raw['oldStart'] ?? null, $timezone, $oldAllDay),
            'oldEnd' => $this->parseDate($raw['oldEnd'] ?? null, $timezone, $oldAllDay),
            'oldAllDay' => $oldAllDay,
        ];
    }

    /**
     * All-day dates are days, not instants: they are read in the application timezone so
     * the day doesn't shift.
     */
    protected function parseDate(mixed $value, string $timezone, bool $allDay): ?CarbonImmutable
    {
        return CalendarConfig::parse($value, $allDay ? (string) config('app.timezone', 'UTC') : $timezone);
    }

    /**
     * The JSON actions behind fetching, dragging and resizing.
     *
     * @return array<Action>
     */
    protected function makeInternalActions(): array
    {
        return [
            Action::make(self::FETCH_EVENTS_ACTION)
                ->action(fn (array $arguments): array => ['events' => $this->getEvents($arguments)]),
            Action::make(self::EVENT_DROP_ACTION)
                ->authorize(fn (?Model $record): bool => $this->isEditable() && $this->canEditEvent($record))
                ->action(fn (array $arguments, ?Model $record): array => $this->respondToChange(
                    $this->onEventDrop(...$this->changedEvents($arguments), record: $record),
                )),
            Action::make(self::EVENT_RESIZE_ACTION)
                ->authorize(fn (?Model $record): bool => $this->isEditable() && $this->canEditEvent($record))
                ->action(fn (array $arguments, ?Model $record): array => $this->respondToChange(
                    $this->onEventResize(...$this->changedEvents($arguments), record: $record),
                )),
        ];
    }

    /**
     * Whether the user may move or resize this event: the resource's (or model policy's)
     * `update` ability. Calendars without a model allow it.
     */
    protected function canEditEvent(?Model $record): bool
    {
        if ($record === null) {
            return $this->getModel() === null;
        }

        $resource = static::$resource;

        return $resource !== null ? $resource::can('update', $record) : Authorization::allows('update', $record);
    }

    /**
     * An event was dragged to a new date. `$event` and `$oldEvent` hold `id`, `start`,
     * `end` (CarbonImmutable in the application timezone, or null) and `allDay`. Return
     * true when the change is saved; false (or an exception) reverts it in the browser.
     *
     * @param  array{id: string|null, start: CarbonImmutable|null, end: CarbonImmutable|null, allDay: bool}  $event
     * @param  array{id: string|null, start: CarbonImmutable|null, end: CarbonImmutable|null, allDay: bool}  $oldEvent
     */
    public function onEventDrop(array $event, array $oldEvent, ?Model $record): bool
    {
        return $this->updateEventDates($event, $record);
    }

    /**
     * An event was resized (its end changed). Same arguments and return value as
     * `onEventDrop()`.
     *
     * @param  array{id: string|null, start: CarbonImmutable|null, end: CarbonImmutable|null, allDay: bool}  $event
     * @param  array{id: string|null, start: CarbonImmutable|null, end: CarbonImmutable|null, allDay: bool}  $oldEvent
     */
    public function onEventResize(array $event, array $oldEvent, ?Model $record): bool
    {
        return $this->updateEventDates($event, $record);
    }

    /**
     * The default drop/resize handler: write the dates to `$startAttribute` /
     * `$endAttribute`. Without them (or a record) nothing is saved and the change reverts.
     *
     * @param  array{id: string|null, start: CarbonImmutable|null, end: CarbonImmutable|null, allDay: bool}  $event
     */
    protected function updateEventDates(array $event, ?Model $record): bool
    {
        if ($record === null || static::$startAttribute === null || $event['start'] === null) {
            return false;
        }

        $record->setAttribute(static::$startAttribute, $event['start']);

        if (static::$endAttribute !== null) {
            $record->setAttribute(static::$endAttribute, $event['end']);
        }

        return $record->save();
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{event: array{id: string|null, start: CarbonImmutable|null, end: CarbonImmutable|null, allDay: bool}, oldEvent: array{id: string|null, start: CarbonImmutable|null, end: CarbonImmutable|null, allDay: bool}}
     */
    protected function changedEvents(array $arguments): array
    {
        $id = isset($arguments['eventId']) && is_string($arguments['eventId']) ? $arguments['eventId'] : null;
        $date = static fn (mixed $value): ?CarbonImmutable => $value instanceof CarbonImmutable ? $value : null;

        if ($date($arguments['start'] ?? null) === null) {
            throw ValidationException::withMessages(['start' => __('alfakit-calendar::calendar.validation.invalid_date', ['attribute' => 'start'])]);
        }

        return [
            'event' => ['id' => $id, 'start' => $date($arguments['start'] ?? null), 'end' => $date($arguments['end'] ?? null), 'allDay' => (bool) ($arguments['allDay'] ?? false)],
            'oldEvent' => ['id' => $id, 'start' => $date($arguments['oldStart'] ?? null), 'end' => $date($arguments['oldEnd'] ?? null), 'allDay' => (bool) ($arguments['oldAllDay'] ?? false)],
        ];
    }

    /**
     * @return array{ok: bool, notifications: array<int, mixed>}
     */
    protected function respondToChange(bool $saved): array
    {
        return ['ok' => $saved, 'notifications' => $this->pullFlashedNotifications(request())];
    }

    /**
     * The visible range's events as FullCalendar objects.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<int, array<string, mixed>>
     */
    protected function getEvents(array $arguments): array
    {
        $start = $arguments['start'] ?? null;
        $end = $arguments['end'] ?? null;

        if (! $start instanceof CarbonImmutable || ! $end instanceof CarbonImmutable || $end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['start' => __('alfakit-calendar::calendar.validation.invalid_date', ['attribute' => 'start'])]);
        }

        if ($start->diffInDays($end) > CalendarConfig::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages(['end' => __('alfakit-calendar::calendar.validation.range_too_long')]);
        }

        $timezone = $this->getTimezone();
        $events = [];

        foreach ($this->fetchEvents(['start' => $start, 'end' => $end, 'timezone' => $timezone]) as $event) {
            if ($event instanceof Event) {
                $events[] = $event->toCalendarArray($timezone);
            } elseif (is_array($event)) {
                $events[] = $this->serializeEventArray($event, $timezone);
            }
        }

        return $events;
    }

    /**
     * A plain event array (as Filament's `EventData::toArray()` returns): DateTime dates
     * become wall time, the URL is sanitised and only JSON values are kept.
     *
     * @param  array<array-key, mixed>  $event
     * @return array<string, mixed>
     */
    protected function serializeEventArray(array $event, string $timezone): array
    {
        $allDay = (bool) ($event['allDay'] ?? false);

        foreach (['start', 'end'] as $key) {
            if (($event[$key] ?? null) instanceof DateTimeInterface) {
                $event[$key] = CalendarConfig::format($event[$key], $timezone, $allDay);
            }
        }

        if (array_key_exists('id', $event) && (is_int($event['id']) || is_string($event['id']))) {
            $event['id'] = (string) $event['id'];
        }

        if (array_key_exists('url', $event)) {
            $event['url'] = \AlfaSix\Alfakit\Support\Url::sanitize($event['url']);
        }

        /** @var array<string, mixed> */
        return array_filter(CalendarConfig::json($event), static fn (mixed $value, int|string $key): bool => is_string($key) && $value !== null, ARRAY_FILTER_USE_BOTH);
    }

    // ---------------------------------------------------------------- Schema

    protected function getSchemaProps(): array
    {
        $views = CalendarConfig::views($this->getViews());
        $views = $views === [] ? ['dayGridMonth'] : $views;
        $initial = $this->getInitialView();
        $actions = $this->getAllActions();

        $headerActions = collect($this->getPreparedHeaderActions())
            ->filter(static fn (Action|ActionGroup $action): bool => $action->isVisible())
            ->map(static fn (Action|ActionGroup $action): array => $action->toSchema())
            ->values()
            ->all();

        $clickAction = ($name = $this->getEventClickActionName()) !== null ? ($actions[$name] ?? null) : null;

        // It opens for the clicked event: an edit or delete action is offered before its
        // record is known and checks it then.
        if ($clickAction instanceof Action && method_exists($clickAction, 'offeredBeforeRecord')) {
            $clickAction->offeredBeforeRecord();
        }
        $selectAction = ($name = $this->getDateSelectActionName()) !== null ? ($actions[$name] ?? null) : null;

        return [
            'heading' => $this->getHeading(),
            'views' => $views,
            'initialView' => $initial !== null && in_array($initial, $views, true) ? $initial : $views[0],
            'locale' => $this->getLocale(),
            'firstDay' => $this->getFirstDay(),
            'timezone' => $this->getTimezone(),
            'editable' => $this->isEditable() ?: null,
            'selectable' => $this->isSelectable() && $selectAction !== null && $selectAction->isVisible() ? true : null,
            'config' => $this->getConfig() ?: null,
            'headerActions' => $headerActions ?: null,
            'eventClickAction' => $clickAction !== null && $clickAction->isVisible() ? $clickAction->toSchema() : null,
            'dateSelectAction' => $selectAction !== null && $selectAction->isVisible() ? $selectAction->toSchema() : null,
        ];
    }
}
