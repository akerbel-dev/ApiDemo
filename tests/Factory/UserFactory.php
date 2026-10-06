<?php

namespace App\Tests\Factory;

use App\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
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
        ];
    }

    protected function initialize(): static
    {
        return $this
        // ->afterInstantiate(function(Post $post): void {})
        ;
    }
}
