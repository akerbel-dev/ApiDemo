<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
        ->andWhere('u.email = :email')
        ->andWhere('u.deletedAt IS NULL')
        ->setParameter('email', $identifier)
        ->getQuery()
        ->getOneOrNullResult();
    }

    public function findAllActive(int $limit, int $offset): array
    {
        return $this->createQueryBuilder('u')
        ->andWhere('u.deletedAt IS NULL')
        ->setMaxResults($limit)
        ->setFirstResult($offset)
        ->orderBy('u.id', 'ASC')
        ->getQuery()
        ->getResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->andWhere('u.deletedAt IS NULL')
        ->getQuery()
        ->getSingleScalarResult();
    }

    public function searchUsers(
        ?string $email,
        ?string $firstName,
        ?string $lastName,
        ?string $role,
        int $limit,
        int $offset,
    ): array {
        $qb = $this->createQueryBuilder('u')
        ->andWhere('u.deletedAt IS NULL');

        if ($email) {
            $qb->andWhere('u.email LIKE :email')
            ->setParameter('email', '%'.$email.'%');
        }

        if ($firstName) {
            $qb->andWhere('u.firstName LIKE :firstName')
            ->setParameter('firstName', '%'.$firstName.'%');
        }

        if ($lastName) {
            $qb->andWhere('u.lastName LIKE :lastName')
            ->setParameter('lastName', '%'.$lastName.'%');
        }

        if ($role) {
            $qb->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%');
        }

        return $qb
        ->orderBy('u.id', 'ASC')
        ->setMaxResults($limit)
        ->setFirstResult($offset)
        ->getQuery()
        ->getResult();
    }

    public function countSearchUsers(
        ?string $email,
        ?string $firstName,
        ?string $lastName,
        ?string $role,
    ): int {
        $qb = $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->andWhere('u.deletedAt IS NULL');

        if ($email) {
            $qb->andWhere('u.email LIKE :email')
            ->setParameter('email', '%'.$email.'%');
        }

        if ($firstName) {
            $qb->andWhere('u.firstName LIKE :firstName')
            ->setParameter('firstName', '%'.$firstName.'%');
        }

        if ($lastName) {
            $qb->andWhere('u.lastName LIKE :lastName')
            ->setParameter('lastName', '%'.$lastName.'%');
        }

        if ($role) {
            $qb->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
