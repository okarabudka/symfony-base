<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testReportsDatabaseCacheAndQueue(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['status' => 'ok', 'checks' => ['database' => 'ok', 'cache' => 'ok', 'queue' => 'ok']],
            json_decode((string) $client->getResponse()->getContent(), true),
        );
    }

    public function testFailsWith503WhenTheDatabaseIsDown(): void
    {
        $client = self::createClient();
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willThrowException(new \RuntimeException('down'));
        self::getContainer()->set('doctrine.dbal.default_connection', $connection);

        $client->request('GET', '/health');

        self::assertResponseStatusCodeSame(503);
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($body);
        self::assertSame('fail', $body['status']);
        self::assertSame(['database' => 'fail', 'cache' => 'ok', 'queue' => 'ok'], $body['checks']);
    }
}
