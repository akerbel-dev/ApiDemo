<?php

namespace App\Service;

use App\Dto\ChangePasswordRequestDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function changePassword(User $user, ChangePasswordRequestDto $dto, bool $isAdmin): void
    {
        $oldPassword = $dto->oldPassword;
        $newPassword = $dto->newPassword;

        if (!$newPassword) {
            throw new \InvalidArgumentException('New password is required');
        }

        if (!$isAdmin) {
            if (!$oldPassword) {
                throw new \InvalidArgumentException('Old password is required');
            }

            if (!$this->passwordHasher->isPasswordValid($user, $oldPassword)) {
                throw new \DomainException('Old password is incorrect');
            }
        }

        $hashed = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashed);

        $this->em->flush();
    }
}
