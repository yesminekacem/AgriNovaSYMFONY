<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
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
}
