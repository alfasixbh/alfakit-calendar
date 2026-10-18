# AlfaKit Calendar

<p><picture><source media="(prefers-color-scheme: dark)" srcset="../../../art/alfasix/alfa-six-micro-white.svg"><img src="../../../art/alfasix/alfa-six-micro-navy.svg" alt="" width="24" height="18" align="top"></picture> <sub>by <a href="https://alfasixbh.com">Alfa Six</a></sub></p>

FullCalendar widgets for [AlfaKit](https://alfakit.dev) panels: month, week, day and list views, actions on events, range selection and drag and drop, all running through AlfaKit's authorised action endpoint. The API follows `saade/filament-fullcalendar`.

## Installation

```bash
composer require alfasix/alfakit-calendar
```

The FullCalendar packages ship with `@alfakit/react`, `@alfakit/vue` and `@alfakit/svelte`; nothing to install on the frontend.

## Register

```php
use AlfaSix\AlfakitCalendar\CalendarPlugin;

$panel->plugin(CalendarPlugin::make()->editable());
```

## Usage

```php
use AlfaSix\AlfakitCalendar\Event;
use AlfaSix\AlfakitCalendar\Widgets\CalendarWidget;

class MeetingsCalendar extends CalendarWidget
{
    protected static ?string $model = Meeting::class;

    protected static ?string $startAttribute = 'starts_at';

    protected static ?string $endAttribute = 'ends_at';

    public function fetchEvents(array $info): array
    {
        return Meeting::query()
            ->whereBetween('starts_at', [$info['start'], $info['end']])
            ->get()
            ->map(fn (Meeting $meeting) => Event::make($meeting)
                ->title($meeting->subject)
                ->start($meeting->starts_at)
                ->end($meeting->ends_at))
            ->all();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('subject')->required(),
            DateTimePicker::make('starts_at')->required(),
            DateTimePicker::make('ends_at'),
        ];
    }
}
```

Add the widget to a dashboard with `->widgets([MeetingsCalendar::class])`. Clicking an event opens a view modal (with edit and delete), selecting a range opens a create modal, and with `editable()` dragging or resizing an event saves its dates.

See the [documentation](https://alfakit.dev/docs/plugins/calendar) for every option.

## License

MIT. See [LICENSE.md](LICENSE.md).
