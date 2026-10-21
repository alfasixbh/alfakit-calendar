<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar\Tests\Fixtures;

use AlfaSix\Alfakit\Actions\Action;

/**
 * Clicking an event edits it (the docs' example in § Actions).
 */
class EditingMeetingsCalendar extends MeetingsCalendar
{
    protected function getEventClickAction(): ?Action
    {
        return $this->editAction()->modalWidth('lg');
    }
}
