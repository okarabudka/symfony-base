<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConfiguration()->setUseSchemaEditorApi(true);
        $schema = new SchemaTool($this->entityManager);
        $metadata = [$this->entityManager->getClassMetadata(User::class)];
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    public function testUpgradesAndStoresTheNewPasswordHash(): void
    {
        $repository = self::getContainer()->get(UserRepository::class);
        $user = new User('ada@example.com', 'old-hash');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $repository->upgradePassword($user, 'new-hash');
        $this->entityManager->clear();

        self::assertSame('new-hash', $repository->findOneBy(['email' => 'ada@example.com'])?->getPassword());
    }

    public function testRejectsOtherUserClasses(): void
    {
        $this->expectException(UnsupportedUserException::class);

        self::getContainer()->get(UserRepository::class)->upgradePassword($this->createStub(PasswordAuthenticatedUserInterface::class), 'hash');
    }
}
