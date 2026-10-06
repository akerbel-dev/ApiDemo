<?php

namespace App\Entity;

final class UserSerializer
{
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
