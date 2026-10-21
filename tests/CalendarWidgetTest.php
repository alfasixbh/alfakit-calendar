<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Tests\Fixtures\AuthTenancy\Panels;
use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use AlfaSix\Alfakit\Tests\Fixtures\Models\TenantUser;
use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use AlfaSix\AlfakitCalendar\CalendarPlugin;
use AlfaSix\AlfakitCalendar\Event;
use AlfaSix\AlfakitCalendar\Support\CalendarConfig;
use AlfaSix\AlfakitCalendar\Tests\Fixtures\Meeting;
use AlfaSix\AlfakitCalendar\Tests\Fixtures\MeetingPolicy;
use AlfaSix\AlfakitCalendar\Tests\Fixtures\MeetingsCalendar;
use AlfaSix\AlfakitCalendar\Tests\TestCase as CalendarTestCase;
use AlfaSix\AlfakitCalendar\Widgets\CalendarWidget;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;

uses(CalendarTestCase::class);

function calendarTest(): CalendarTestCase
{
    return CalendarTestCase::current();
}

function calendarPanel(?CalendarPlugin $plugin = null): Panel
{
    return Panels::register('cal', fn (Panel $panel): Panel => $panel
        ->timezone('Asia/Bahrain')
        ->plugin($plugin ?? CalendarPlugin::make()->editable())
        ->pages([Dashboard::class])
        ->widgets([MeetingsCalendar::class]));
}

function calendarUser(): User
{
    return User::query()->create(['name' => 'Cal', 'email' => 'cal'.uniqid().'@example.com', 'password' => 'secret']);
}

/**
 * @return array{0: string, 1: array<string, mixed>}
 */
function calendarWidget(CalendarTestCase $test, string $url = '/cal'): array
{
    $props = $test->inertiaProps($url);
    $widgets = collect($props['page']['content'])->firstWhere('key', 'widgets')['widgets'] ?? [];

    return [$props['page']['token'], collect($widgets)->firstWhere('type', 'Calendar')];
}

beforeEach(function (): void {
    MeetingsCalendar::$fetched = [];
    MeetingPolicy::$viewAny = true;
    Gate::policy(Meeting::class, MeetingPolicy::class);
});

it('registers on a panel and shares its frontend strings', function (): void {
    $panel = calendarPanel();

    expect($panel->hasPlugin('calendar'))->toBeTrue()
        ->and($panel->getPlugin('calendar'))->toBeInstanceOf(CalendarPlugin::class)
        ->and($panel->getFrontendTranslations()['calendar']['today'])->toBe('Today');
});

it('renders the widget schema with views, actions, timezone and safe config', function (): void {
    calendarPanel(CalendarPlugin::make()->editable()->firstDay(6)->config(['weekNumbers' => true, 'events' => '/evil']));
    calendarTest()->actingAs(calendarUser());

    [, $widget] = calendarWidget(calendarTest());

    expect($widget['component'])->toBe('widget:calendar')
        ->and($widget['heading'])->toBe('Meetings')
        ->and($widget['views'])->toBe(['dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listWeek'])
        ->and($widget['initialView'])->toBe('dayGridMonth')
        ->and($widget['timezone'])->toBe('Asia/Bahrain')
        ->and($widget['firstDay'])->toBe(6)
        ->and($widget['editable'])->toBeTrue()
        ->and($widget['selectable'])->toBeTrue()
        ->and($widget['config'])->toBe(['weekNumbers' => true, 'dayMaxEvents' => true, 'nowIndicator' => true])
        ->and($widget['headerActions'][0]['name'])->toBe('create')
        ->and($widget['eventClickAction']['name'])->toBe('view')
        ->and($widget['dateSelectAction']['name'])->toBe('create');
});

it('offers the event click action before an event is clicked by the viewAny ability', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());

    // `view(User, Meeting)` needs a meeting: the record-less action asks `viewAny`.
    [, $widget] = calendarWidget(calendarTest());
    expect($widget['eventClickAction']['name'])->toBe('view');

    MeetingPolicy::$viewAny = false;

    [, $widget] = calendarWidget(calendarTest());
    expect($widget['eventClickAction'] ?? null)->toBeNull();
});

it('offers editAction() as the event click action and checks update when an event is opened', function (): void {
    Panels::register('cal', fn (Panel $panel): Panel => $panel
        ->plugin(CalendarPlugin::make()->editable())
        ->pages([Dashboard::class])
        ->widgets([AlfaSix\AlfakitCalendar\Tests\Fixtures\EditingMeetingsCalendar::class]));
    calendarTest()->actingAs(calendarUser());
    $open = Meeting::query()->create(['title' => 'Planning', 'starts_at' => '2026-10-13 06:00:00']);
    $locked = Meeting::query()->create(['title' => 'Locked', 'starts_at' => '2026-10-14 06:00:00']);

    // `update(User, Meeting)` needs a meeting: before one is clicked the action is offered.
    [$token, $widget] = calendarWidget(calendarTest());
    expect($widget['eventClickAction']['name'] ?? null)->toBe('edit');

    $modal = fn (Meeting $meeting) => calendarTest()->getJson('/cal/_alfakit/actions/modal?'.http_build_query([
        'token' => $token, 'component' => "widget:{$widget['id']}", 'action' => 'edit', 'record' => $meeting->id,
    ]));

    expect($modal($open)->assertOk()->json('form.state.title'))->toBe('Planning');
    $modal($locked)->assertForbidden();

    // Offered without a record, it never runs without one.
    calendarTest()->widgetAction('cal', $token, $widget['id'], 'edit', ['data' => ['title' => 'X', 'starts_at' => '2026-10-15 06:00:00']])->assertForbidden();
    expect(Meeting::query()->where('title', 'X')->exists())->toBeFalse();

    MeetingPolicy::$viewAny = false;

    [, $widget] = calendarWidget(calendarTest());
    expect($widget['eventClickAction'] ?? null)->toBeNull();
});

it('sends a skeleton stand-in for lazy calendars', function (): void {
    $widget = new class extends CalendarWidget
    {
        public function fetchEvents(array $info): array
        {
            return [];
        }
    };

    expect($widget->toLazySchema())->toMatchArray(['type' => 'Calendar', 'component' => 'widget:calendar', 'lazy' => true]);
});

it('keeps only JSON options the widget does not control', function (): void {
    expect(CalendarConfig::sanitize([
        'dayMaxEvents' => 3,
        'eventContent' => 'html',
        'plugins' => ['x'],
        'select' => true,
        'eventDidMount' => 'js',
        'businessHours' => ['daysOfWeek' => [1, 2, 3], 'startTime' => '08:00', 'callback' => new stdClass],
        'bad-key' => 1,
        'slotDuration' => INF,
        'views' => ['timeGrid' => ['dayMaxEventRows' => 4]],
    ]))->toBe([
        'dayMaxEvents' => 3,
        'businessHours' => ['daysOfWeek' => [1, 2, 3], 'startTime' => '08:00'],
        'views' => ['timeGrid' => ['dayMaxEventRows' => 4]],
    ]);
});

it('fetches the events of a range through the widget endpoint in the calendar timezone', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    Meeting::query()->create(['title' => 'Standup', 'starts_at' => '2026-10-13 06:00:00', 'ends_at' => '2026-10-13 06:30:00']);
    Meeting::query()->create(['title' => 'Next month', 'starts_at' => '2026-12-13 06:00:00']);
    [$token, $widget] = calendarWidget(calendarTest());

    $events = calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::FETCH_EVENTS_ACTION, [
        'arguments' => ['calendar' => ['start' => '2026-10-01T00:00:00Z', 'end' => '2026-11-01T00:00:00Z']],
    ])->assertOk()->json('result.events');

    expect($events)->toHaveCount(1)
        ->and($events[0])->toMatchArray([
            'title' => 'Standup',
            'start' => '2026-10-13T09:00:00',
            'end' => '2026-10-13T09:30:00',
            'allDay' => false,
            'extendedProps' => ['room_name' => 'Blue'],
        ])
        ->and(MeetingsCalendar::$fetched[0]['start']->toDateTimeString())->toBe('2026-09-30 21:00:00')
        ->and(MeetingsCalendar::$fetched[0]['timezone'])->toBe('Asia/Bahrain');
});

it('rejects invalid and oversized ranges', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    [$token, $widget] = calendarWidget(calendarTest());

    calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::FETCH_EVENTS_ACTION, [
        'arguments' => ['calendar' => ['start' => 'yesterday', 'end' => '2026-11-01']],
    ])->assertUnprocessable();

    calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::FETCH_EVENTS_ACTION, [
        'arguments' => ['calendar' => ['start' => '2020-01-01', 'end' => '2026-11-01']],
    ])->assertUnprocessable();

    expect(MeetingsCalendar::$fetched)->toBe([]);
});

it('saves a dropped event and answers ok', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    $meeting = Meeting::query()->create(['title' => 'Standup', 'starts_at' => '2026-10-13 06:00:00', 'ends_at' => '2026-10-13 06:30:00']);
    [$token, $widget] = calendarWidget(calendarTest());

    $result = calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::EVENT_DROP_ACTION, [
        'record' => (string) $meeting->id,
        'arguments' => ['calendar' => [
            'start' => '2026-10-15T10:00:00', 'end' => '2026-10-15T10:30:00', 'allDay' => false,
            'oldStart' => '2026-10-13T09:00:00', 'oldEnd' => '2026-10-13T09:30:00',
        ]],
    ])->assertOk()->json('result');

    expect($result['ok'])->toBeTrue()
        ->and($meeting->refresh()->starts_at->toDateTimeString())->toBe('2026-10-15 07:00:00')
        ->and($meeting->ends_at?->toDateTimeString())->toBe('2026-10-15 07:30:00');
});

it('saves a resized event', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    $meeting = Meeting::query()->create(['title' => 'Standup', 'starts_at' => '2026-10-13 06:00:00', 'ends_at' => '2026-10-13 06:30:00']);
    [$token, $widget] = calendarWidget(calendarTest());

    calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::EVENT_RESIZE_ACTION, [
        'record' => $meeting->id,
        'arguments' => ['calendar' => ['start' => '2026-10-13T09:00:00', 'end' => '2026-10-13T11:00:00']],
    ])->assertOk()->assertJsonPath('result.ok', true);

    expect($meeting->refresh()->ends_at?->toDateTimeString())->toBe('2026-10-13 08:00:00');
});

it('refuses drops the policy denies and drops on calendars that are not editable', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    $locked = Meeting::query()->create(['title' => 'Locked', 'starts_at' => '2026-10-13 06:00:00']);
    [$token, $widget] = calendarWidget(calendarTest());
    $payload = ['record' => $locked->id, 'arguments' => ['calendar' => ['start' => '2026-10-20T09:00:00']]];

    calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::EVENT_DROP_ACTION, $payload)->assertForbidden();

    expect($locked->refresh()->starts_at->toDateTimeString())->toBe('2026-10-13 06:00:00');

    $open = Meeting::query()->create(['title' => 'Open', 'starts_at' => '2026-10-13 06:00:00']);
    alfakit()->getPanel('cal')->plugin(CalendarPlugin::make()->editable(false));

    calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::EVENT_DROP_ACTION, [...$payload, 'record' => $open->id])->assertForbidden();

    expect($open->refresh()->starts_at->toDateTimeString())->toBe('2026-10-13 06:00:00');
});

it('answers 404 for events that do not exist', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    [$token, $widget] = calendarWidget(calendarTest());

    calendarTest()->widgetAction('cal', $token, $widget['id'], CalendarWidget::EVENT_DROP_ACTION, [
        'record' => 999, 'arguments' => ['calendar' => ['start' => '2026-10-20T09:00:00']],
    ])->assertNotFound();
});

it('opens the event click action for the event record and prefills the date select action', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    $meeting = Meeting::query()->create(['title' => 'Planning', 'starts_at' => '2026-10-13 06:00:00']);
    [$token, $widget] = calendarWidget(calendarTest());

    $view = calendarTest()->getJson('/cal/_alfakit/actions/modal?'.http_build_query([
        'token' => $token, 'component' => "widget:{$widget['id']}", 'action' => 'view', 'record' => $meeting->id,
    ]))->assertOk()->json();

    expect($view['heading'])->toContain('Planning')
        ->and($view['form']['state']['title'])->toBe('Planning');

    $create = calendarTest()->getJson('/cal/_alfakit/actions/modal?'.http_build_query([
        'token' => $token, 'component' => "widget:{$widget['id']}", 'action' => 'create',
        'arguments' => ['calendar' => ['start' => '2026-10-20T09:00:00', 'end' => '2026-10-20T10:00:00']],
    ]))->assertOk()->json();

    expect($create['form']['state']['starts_at'])->toStartWith('2026-10-20 09:00');
});

it('creates an event from the date select action', function (): void {
    calendarPanel();
    calendarTest()->actingAs(calendarUser());
    [$token, $widget] = calendarWidget(calendarTest());

    calendarTest()->post('/cal/_alfakit/actions', [
        'token' => $token,
        'component' => "widget:{$widget['id']}",
        'action' => 'create',
        'data' => ['title' => 'Workshop', 'starts_at' => '2026-10-20 09:00', 'ends_at' => '2026-10-20 10:00'],
        'arguments' => ['calendar' => ['start' => '2026-10-20T09:00:00']],
    ], ['X-Inertia' => 'true', 'X-Inertia-Version' => ''])->assertRedirect();

    expect(Meeting::query()->where('title', 'Workshop')->firstOrFail()->starts_at->toDateTimeString())->toBe('2026-10-20 06:00:00');
});

it('scopes events and their records to the current tenant', function (): void {
    $panel = Panels::register('calteam', fn (Panel $panel): Panel => $panel
        ->tenant(Team::class, slugAttribute: 'slug')
        ->plugin(CalendarPlugin::make()->editable())
        ->pages([Dashboard::class])
        ->widgets([MeetingsCalendar::class]));

    $user = TenantUser::query()->create(['name' => 'T', 'email' => 't@example.com', 'password' => 'secret']);
    $mine = Team::query()->create(['name' => 'Mine', 'slug' => 'mine']);
    $other = Team::query()->create(['name' => 'Other', 'slug' => 'other']);
    $user->teams()->attach($mine);
    calendarTest()->actingAs($user);

    Meeting::query()->create(['team_id' => $mine->id, 'title' => 'Ours', 'starts_at' => '2026-10-13 06:00:00']);
    $theirs = Meeting::query()->create(['team_id' => $other->id, 'title' => 'Theirs', 'starts_at' => '2026-10-13 07:00:00']);

    [$token, $widget] = calendarWidget(calendarTest(), '/calteam/mine');

    $events = calendarTest()->widgetAction('calteam/mine', $token, $widget['id'], CalendarWidget::FETCH_EVENTS_ACTION, [
        'arguments' => ['calendar' => ['start' => '2026-10-01', 'end' => '2026-11-01']],
    ])->assertOk()->json('result.events');

    expect(collect($events)->pluck('title')->all())->toBe(['Ours']);

    calendarTest()->widgetAction('calteam/mine', $token, $widget['id'], CalendarWidget::EVENT_DROP_ACTION, [
        'record' => $theirs->id, 'arguments' => ['calendar' => ['start' => '2026-10-20T09:00:00']],
    ])->assertNotFound();

    expect($panel->hasTenancy())->toBeTrue();
});

it('serialises events with wall-time dates', function (): void {
    $event = Event::make(5)
        ->title('Launch')
        ->start(CarbonImmutable::parse('2026-10-13 06:00:00', 'UTC'))
        ->end('2026-10-13T12:00:00')
        ->url('javascript:alert(1)')
        ->backgroundColor('#f97316')
        ->toCalendarArray('Asia/Bahrain');

    expect($event)->toBe([
        'id' => '5',
        'title' => 'Launch',
        'start' => '2026-10-13T09:00:00',
        'end' => '2026-10-13T12:00:00',
        'allDay' => false,
        'backgroundColor' => '#f97316',
    ])->and(Event::make()->title('Holiday')->start(CarbonImmutable::parse('2026-10-13', 'UTC'))->allDay()->toCalendarArray('Asia/Tokyo')['start'])->toBe('2026-10-13');
});

it('publishes its translations with the alfakit-calendar-translations tag', function (): void {
    $paths = Illuminate\Support\ServiceProvider::pathsToPublish(AlfaSix\AlfakitCalendar\AlfakitCalendarServiceProvider::class, 'alfakit-calendar-translations');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('resources/lang')
        ->and(is_dir((string) array_key_first($paths)))->toBeTrue()
        ->and(array_values($paths)[0])->toBe(app()->langPath('vendor/alfakit-calendar'));
});
