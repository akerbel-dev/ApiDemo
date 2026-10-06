<?php

namespace App\Tests\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->unique()->email(),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'roles' => ['ROLE_USER'],
            'deletedAt' => null,
            'password' => 'Strong123!',
        ];
    }

    protected function initialize(): static
    {
        return $this
        ->afterInstantiate(function (User $user): void {
            $plain = $user->getPassword();
            $hashed = $this->passwordHasher->hashPassword($user, $plain);
            $user->setPassword($hashed);
        });
    }
}
