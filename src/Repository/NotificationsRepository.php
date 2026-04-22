<?php

namespace App\Repository;

use App\Entity\Notifications;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notifications::class);
    }

    public function findUnreadByRecipient(int $recipientId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipientId = :recipientId')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('recipientId', $recipientId)
            ->setParameter('isRead', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByRecipient(int $recipientId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipientId = :recipientId')
            ->setParameter('recipientId', $recipientId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}