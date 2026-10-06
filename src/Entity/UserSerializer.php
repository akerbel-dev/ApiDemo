<?php

namespace App\Entity;

final class UserSerializer
{
    /**
     * @return array<string, array<string>|int|string|null>
     */
    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'deletedAt' => $user->getDeletedAt()?->format('c'),
        ];
    }
}
