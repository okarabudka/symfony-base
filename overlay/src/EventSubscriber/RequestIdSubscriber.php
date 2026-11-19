<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Tags every log entry of a request with a request id and returns it to the client.
 */
#[AsMonologProcessor]
final class RequestIdSubscriber implements EventSubscriberInterface, ProcessorInterface, ResetInterface
{
    public const HEADER = 'X-Request-Id';

    private ?string $requestId = null;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 512],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $incoming = $event->getRequest()->headers->get(self::HEADER);
        $this->requestId = \is_string($incoming) && 1 === preg_match('/^[A-Za-z0-9._-]{1,128}$/', $incoming)
            ? $incoming
            : Uuid::v7()->toRfc4122();
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() && null !== $this->requestId) {
            $event->getResponse()->headers->set(self::HEADER, $this->requestId);
        }
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (null === $this->requestId) {
            return $record;
        }

        return $record->with(extra: [...$record->extra, 'request_id' => $this->requestId]);
    }

    public function reset(): void
    {
        $this->requestId = null;
    }
}
