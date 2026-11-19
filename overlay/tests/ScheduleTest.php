<?php

declare(strict_types=1);

namespace App\Tests;

use App\Schedule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class ScheduleTest extends TestCase
{
    public function testTheDefaultScheduleHasNoTasks(): void
    {
        self::assertSame([], (new Schedule(new ArrayAdapter()))->getSchedule()->getRecurringMessages());
    }
}
