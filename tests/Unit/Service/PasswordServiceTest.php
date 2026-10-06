<?php

namespace App\Tests\Unit\Service;

use App\Dto\ChangePasswordRequestDto;
use App\Entity\User;
use App\Service\PasswordService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(PasswordService::class)]
final class PasswordServiceTest extends TestCase
{
    use Factories;
    use ResetDatabase;

    #[Test]
    public function adminCanChangePasswordWithoutOldPassword(): void
    {
        $user = new User();

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-pass');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new PasswordService($hasher, $em);

        $dto = new ChangePasswordRequestDto([
            'newPassword' => 'Secret123!',
        ]);

        $service->changePassword($user, $dto, isAdmin: true);

        $this->assertSame('hashed-pass', $user->getPassword());
    }

    #[Test]
    public function nonAdminMustProvideOldPassword(): void
    {
        $user = new User();

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $em = $this->createStub(EntityManagerInterface::class);

        $service = new PasswordService($hasher, $em);

        $dto = new ChangePasswordRequestDto([
            'newPassword' => 'Secret123!',
            'oldPassword' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Old password is required');

        $service->changePassword($user, $dto, isAdmin: false);
    }

    #[Test]
    public function nonAdminOldPasswordMustBeValid(): void
    {
        $user = new User();

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(false);

        $em = $this->createStub(EntityManagerInterface::class);

        $service = new PasswordService($hasher, $em);

        $dto = new ChangePasswordRequestDto([
            'newPassword' => 'Secret123!',
            'oldPassword' => 'wrong-pass',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Old password is incorrect');

        $service->changePassword($user, $dto, isAdmin: false);
    }

    #[Test]
    public function newPasswordIsRequired(): void
    {
        $user = new User();

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $em = $this->createStub(EntityManagerInterface::class);

        $service = new PasswordService($hasher, $em);

        $dto = new ChangePasswordRequestDto([
            'newPassword' => null,
            'oldPassword' => 'whatever',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('New password is required');

        $service->changePassword($user, $dto, isAdmin: false);
    }

    #[Test]
    public function nonAdminValidOldPasswordChangesPassword(): void
    {
        $user = new User();

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(true);
        $hasher->method('hashPassword')->willReturn('hashed-pass');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $service = new PasswordService($hasher, $em);

        $dto = new ChangePasswordRequestDto([
            'newPassword' => 'Secret123!',
            'oldPassword' => 'correct-pass',
        ]);

        $service->changePassword($user, $dto, isAdmin: false);

        $this->assertSame('hashed-pass', $user->getPassword());
    }
}
