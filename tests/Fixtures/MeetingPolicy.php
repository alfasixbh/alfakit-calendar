<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitCalendar\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Meetings titled "Locked" can't be changed. `view` needs the meeting, like most policies.
 */
class MeetingPolicy
{
    public static bool $viewAny = true;

    public function viewAny(?Authenticatable $user): bool
    {
        return self::$viewAny;
    }

    public function view(?Authenticatable $user, Meeting $meeting): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, Meeting $meeting): bool
    {
        return $meeting->title !== 'Locked';
    }
}
