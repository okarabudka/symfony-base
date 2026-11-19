<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ProductionCommandGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class ProductionCommandGuardTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function cases(): iterable
    {
        foreach (ProductionCommandGuard::BLOCKED as $name) {
            yield "{$name} in prod" => ['prod', $name, false];
            yield "{$name} in dev" => ['dev', $name, true];
        }
        yield 'other command in prod' => ['prod', 'cache:clear', true];
    }

    public function testListensToConsoleCommands(): void
    {
        self::assertSame([ConsoleEvents::COMMAND => 'onCommand'], ProductionCommandGuard::getSubscribedEvents());
    }

    #[DataProvider('cases')]
    public function testDestructiveCommandsAreBlockedInProductionOnly(string $environment, string $command, bool $runs): void
    {
        $event = new ConsoleCommandEvent(new Command($command), new ArrayInput([]), new BufferedOutput());

        (new ProductionCommandGuard($environment))->onCommand($event);

        self::assertSame($runs, $event->commandShouldRun());
    }
}
