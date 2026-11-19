<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testStartPageShowsTheApplicationName(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        $appName = $_SERVER['APP_NAME'] ?? null;
        self::assertIsString($appName);
        self::assertSelectorTextSame('title', $appName);
    }
}
