<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\EventSubscriber\RequestIdSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class RequestIdTest extends WebTestCase
{
    public function testEveryResponseCarriesAGeneratedRequestId(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health');

        $id = $client->getResponse()->headers->get(RequestIdSubscriber::HEADER);
        self::assertTrue(Uuid::isValid((string) $id));
    }

    public function testAValidIncomingRequestIdIsKept(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health', server: ['HTTP_X_REQUEST_ID' => 'abc-123']);

        self::assertResponseHeaderSame(RequestIdSubscriber::HEADER, 'abc-123');
    }

    public function testAnInvalidIncomingRequestIdIsReplaced(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health', server: ['HTTP_X_REQUEST_ID' => "bad id\n"]);

        self::assertTrue(Uuid::isValid((string) $client->getResponse()->headers->get(RequestIdSubscriber::HEADER)));
    }
}
