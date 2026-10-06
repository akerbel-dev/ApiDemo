<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\PaginationDto;
use App\Dto\UpdateUserRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserService::class)]
final class UserServiceTest extends TestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->createStub(UserRepository::class);
    }

    #[Test]
    public function updateUserEmail(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new UserService($this->repo, $em);

        $user = new User();
        $user->setEmail('old@example.com');

        $dto = new UpdateUserRequestDto(['email' => 'new@example.com']);

        $updated = $service->updateUser($user, $dto);

        $this->assertSame('new@example.com', $updated->getEmail());
    }

    #[Test]
    public function updateUserFirstName(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new UserService($this->repo, $em);

        $user = new User();
        $user->setFirstName('Old');

        $dto = new UpdateUserRequestDto(['firstName' => 'New']);

        $updated = $service->updateUser($user, $dto);

        $this->assertSame('New', $updated->getFirstName());
    }

    #[Test]
    public function updateUserLastName(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new UserService($this->repo, $em);

        $user = new User();
        $user->setLastName('Old');

        $dto = new UpdateUserRequestDto(['lastName' => 'New']);

        $updated = $service->updateUser($user, $dto);

        $this->assertSame('New', $updated->getLastName());
    }

    #[Test]
    public function updateUserDoesNotChangeFieldsWhenNull(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new UserService($this->repo, $em);

        $user = new User();
        $user->setEmail('old@example.com');
        $user->setFirstName('OldFirst');
        $user->setLastName('OldLast');

        $dto = new UpdateUserRequestDto([]);

        $updated = $service->updateUser($user, $dto);

        $this->assertSame('old@example.com', $updated->getEmail());
        $this->assertSame('OldFirst', $updated->getFirstName());
        $this->assertSame('OldLast', $updated->getLastName());
    }

    #[Test]
    public function softDeleteSetsDeletedFlag(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new UserService($this->repo, $em);

        $user = new User();

        $service->softDelete($user);

        $this->assertTrue($user->isDeleted());
        $this->assertNotNull($user->getDeletedAt());
    }

    #[Test]
    public function listActiveUsers(): void
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('findAllActive')->willReturn([new User()]);

        $em = $this->createStub(EntityManagerInterface::class);

        $service = new UserService($repo, $em);

        $pagination = new PaginationDto(1, 10, 0);

        $result = $service->listActiveUsers($pagination);

        $this->assertCount(1, $result);
    }

    #[Test]
    public function countActiveUsers(): void
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('countActive')->willReturn(5);

        $em = $this->createStub(EntityManagerInterface::class);

        $service = new UserService($repo, $em);

        $this->assertSame(5, $service->countActiveUsers());
    }

    #[Test]
    public function searchUsers(): void
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('searchUsers')->willReturn([new User()]);

        $em = $this->createStub(EntityManagerInterface::class);

        $service = new UserService($repo, $em);

        $pagination = new PaginationDto(1, 10, 0);

        $result = $service->searchUsers('a@example.com', 'John', 'Doe', 'ROLE_USER', $pagination);

        $this->assertCount(1, $result);
    }

    #[Test]
    public function countSearchUsers(): void
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('countSearchUsers')->willReturn(3);

        $em = $this->createStub(EntityManagerInterface::class);

        $service = new UserService($repo, $em);

        $count = $service->countSearchUsers('a@example.com', 'John', 'Doe', 'ROLE_USER');

        $this->assertSame(3, $count);
    }
}
