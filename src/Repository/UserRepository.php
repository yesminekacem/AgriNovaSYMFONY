<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByVerificationToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.verificationToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Return users that have enrolled face data (non-null, non-empty)
     * Used by face detection logic to compare probe against enrolled images.
     * @return User[]
     */
    public function findAllWithFaceData(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.faceData IS NOT NULL')
            ->andWhere("u.faceData <> ''")
            ->getQuery()
            ->getResult();
    }

    /**
     * Upgrade the hashed password of a user (called automatically on successful login
     * when the password needs rehashing according to the configured hasher).
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }
}
