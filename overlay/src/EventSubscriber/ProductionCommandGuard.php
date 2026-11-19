<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Blocks commands that destroy data when the application runs in production.
 */
final class ProductionCommandGuard implements EventSubscriberInterface
{
    public const BLOCKED = [
        'doctrine:database:drop',
        'doctrine:schema:drop',
        'doctrine:fixtures:load',
    ];

    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => 'onCommand'];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $name = $event->getCommand()?->getName();

        if ('prod' === $this->environment && \in_array($name, self::BLOCKED, true)) {
            $event->getOutput()->writeln(\sprintf('<error>%s is disabled in production.</error>', $name));
            $event->disableCommand();
        }
    }
}
