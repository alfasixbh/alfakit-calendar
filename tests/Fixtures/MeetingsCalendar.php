<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar\Tests\Fixtures;

use AlfaSix\Alfakit\Forms\Components\DateTimePicker;
use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\AlfakitCalendar\Event;
use AlfaSix\AlfakitCalendar\Widgets\CalendarWidget;
use Illuminate\Database\Eloquent\Model;

class MeetingsCalendar extends CalendarWidget
{
    protected static bool $isLazy = false;

    protected static ?string $model = Meeting::class;

    protected static ?string $startAttribute = 'starts_at';

    protected static ?string $endAttribute = 'ends_at';

    protected ?string $heading = 'Meetings';

    /** @var array<int, array<string, mixed>> */
    public static array $fetched = [];

    public function fetchEvents(array $info): array
    {
        static::$fetched[] = $info;

        return ($this->getEloquentQuery() ?? Meeting::query())
            ->where('starts_at', '<', $info['end'])
            ->where('starts_at', '>=', $info['start'])
            ->get()
            ->map(fn (Model $meeting): Event => Event::make($meeting)
                ->title((string) $meeting->getAttribute('title'))
                ->start($meeting->getAttribute('starts_at'))
                ->end($meeting->getAttribute('ends_at'))
                ->extendedProps(['room_name' => 'Blue']))
            ->all();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')->required(),
            DateTimePicker::make('starts_at')->required(),
            DateTimePicker::make('ends_at'),
        ];
    }

    public function config(): array
    {
        return ['dayMaxEvents' => true, 'eventClick' => 'alert(1)', 'nowIndicator' => true];
    }
}
