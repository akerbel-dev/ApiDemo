<?php

namespace App\Service;

use App\Dto\PaginationDto;
use App\Dto\UpdateUserRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function updateUser(User $user, UpdateUserRequestDto $dto): User
    {
        if (null !== $dto->email) {
            $existing = $this->userRepository->findOneBy(['email' => $dto->email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                throw new \DomainException('Email already taken');
            }
            $user->setEmail($dto->email);
        }

        if (null !== $dto->firstName) {
            $user->setFirstName($dto->firstName);
        }

        if (null !== $dto->lastName) {
            $user->setLastName($dto->lastName);
        }

        $this->em->flush();

        return $user;
    }

    public function softDelete(User $user): void
    {
        $user->softDelete();
        $this->em->flush();
    }

    public function listActiveUsers(PaginationDto $pagination): array
    {
        return $this->userRepository->findAllActive(
            limit: $pagination->limit,
            offset: $pagination->offset
        );
    }

    public function countActiveUsers(): int
    {
        return $this->userRepository->countActive();
    }

    public function searchUsers(
        ?string $email,
        ?string $firstName,
        ?string $lastName,
        ?string $role,
        PaginationDto $pagination,
    ): array {
        return $this->userRepository->searchUsers(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            role: $role,
            limit: $pagination->limit,
            offset: $pagination->offset
        );
    }

    public function countSearchUsers(
        ?string $email,
        ?string $firstName,
        ?string $lastName,
        ?string $role,
    ): int {
        return $this->userRepository->countSearchUsers(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            role: $role
        );
    }
}
