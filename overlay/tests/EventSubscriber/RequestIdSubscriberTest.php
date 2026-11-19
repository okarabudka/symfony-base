<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\RequestIdSubscriber;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestIdSubscriberTest extends TestCase
{
    public function testListensEarlyOnRequestAndOnResponse(): void
    {
        $events = RequestIdSubscriber::getSubscribedEvents();

        self::assertSame(['onRequest', 512], $events[KernelEvents::REQUEST]);
        self::assertSame('onResponse', $events[KernelEvents::RESPONSE]);
    }

    public function testAddsTheRequestIdToLogRecordsAndResetsBetweenRequests(): void
    {
        $subscriber = new RequestIdSubscriber();
        $subscriber->onRequest($this->requestEvent(Request::create('/', server: ['HTTP_X_REQUEST_ID' => 'log-1'])));

        self::assertSame('log-1', $subscriber($this->record())->extra['request_id']);

        $subscriber->reset();
        self::assertArrayNotHasKey('request_id', $subscriber($this->record())->extra);
    }

    public function testSubRequestsKeepTheMainRequestId(): void
    {
        $subscriber = new RequestIdSubscriber();
        $subscriber->onRequest($this->requestEvent(Request::create('/', server: ['HTTP_X_REQUEST_ID' => 'main'])));
        $subscriber->onRequest($this->requestEvent(Request::create('/', server: ['HTTP_X_REQUEST_ID' => 'sub']), HttpKernelInterface::SUB_REQUEST));

        $response = new Response();
        $subscriber->onResponse(new ResponseEvent($this->createStub(HttpKernelInterface::class), Request::create('/'), HttpKernelInterface::SUB_REQUEST, $response));

        self::assertSame('main', $subscriber($this->record())->extra['request_id']);
        self::assertFalse($response->headers->has(RequestIdSubscriber::HEADER));
    }

    public function testNoHeaderWithoutARequest(): void
    {
        $response = new Response();
        (new RequestIdSubscriber())->onResponse(new ResponseEvent($this->createStub(HttpKernelInterface::class), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        self::assertFalse($response->headers->has(RequestIdSubscriber::HEADER));
    }

    private function requestEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $type);
    }

    private function record(): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'app', Level::Info, 'hello');
    }
}
