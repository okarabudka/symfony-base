<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEveryUserHasRoleUserOnce(): void
    {
        $user = (new User('ada@example.com'))->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame(['ROLE_USER'], (new User('bob@example.com'))->getRoles());
    }

    public function testTheEmailIdentifiesTheUser(): void
    {
        $user = (new User('ada@example.com'))->setEmail('ada@example.org');

        self::assertSame('ada@example.org', $user->getEmail());
        self::assertSame('ada@example.org', $user->getUserIdentifier());
        self::assertNull($user->getId());
    }

    public function testTheSessionNeverStoresThePasswordHash(): void
    {
        $user = (new User('ada@example.com'))->setPassword('$2y$hash');

        self::assertSame('$2y$hash', $user->getPassword());
        self::assertStringNotContainsString('$2y$hash', serialize($user));
    }
}
