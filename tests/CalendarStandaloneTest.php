<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use AlfaSix\AlfakitCalendar\CalendarPlugin;
use AlfaSix\AlfakitCalendar\Tests\Fixtures\Meeting;
use AlfaSix\AlfakitCalendar\Tests\Fixtures\MeetingPolicy;
use AlfaSix\AlfakitCalendar\Tests\Fixtures\MeetingsCalendar;
use AlfaSix\AlfakitCalendar\Tests\Fixtures\MeetingsOverview;
use AlfaSix\AlfakitCalendar\Tests\TestCase as CalendarTestCase;
use AlfaSix\AlfakitCalendar\Widgets\CalendarWidget;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
 * The calendar widget outside panels: a `Standalone\Widgets` component in an app page, with
 * the plugin registered on the standalone panel (`alfakit.standalone.plugins`).
 */

uses(CalendarTestCase::class);

beforeEach(function (): void {
    MeetingsCalendar::$fetched = [];
    MeetingPolicy::$viewAny = true;
    Gate::policy(Meeting::class, MeetingPolicy::class);

    alfakit()->getStandalonePanel()->plugin(CalendarPlugin::make());

    Route::middleware('web')->get('/meetings', fn () => Inertia::render('meetings', ['calendar' => MeetingsOverview::make()]));
});

it('renders the calendar widget in an app page and fetches events through the standalone endpoints', function (): void {
    $this->actingAs(User::query()->create(['name' => 'Cal', 'email' => 'cal@example.com', 'password' => 'secret']));
    Meeting::query()->create(['title' => 'Standup', 'starts_at' => '2026-10-13 06:00:00', 'ends_at' => '2026-10-13 06:30:00']);

    $calendar = $this->inertiaProps('/meetings')['calendar'];
    $widget = collect(collect($calendar['content'])->firstWhere('key', 'headerWidgets')['widgets'] ?? [])->firstWhere('type', 'Calendar');

    expect($widget['component'])->toBe('widget:calendar')
        ->and($calendar['translations']['calendar']['today'])->toBe('Today');

    $events = $this->postJson('/_alfakit/actions', [
        'token' => $calendar['token'],
        'component' => 'widget:'.$widget['id'],
        'action' => CalendarWidget::FETCH_EVENTS_ACTION,
        'arguments' => ['calendar' => ['start' => '2026-10-01T00:00:00Z', 'end' => '2026-11-01T00:00:00Z']],
    ])->assertOk()->json('result.events');

    expect($events)->toHaveCount(1)
        ->and($events[0]['title'])->toBe('Standup');
});
