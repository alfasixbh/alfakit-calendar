<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar\Tests\Fixtures;

use AlfaSix\Alfakit\Standalone\Widgets;

/**
 * The calendar widget outside a panel, in one of the app's own Inertia pages.
 */
class MeetingsOverview extends Widgets
{
    public function widgets(): array
    {
        return [MeetingsCalendar::class];
    }

    public function widgetColumns(): int|array
    {
        return 1;
    }
}
