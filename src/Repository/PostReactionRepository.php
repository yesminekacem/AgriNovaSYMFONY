<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\PostReaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostReaction::class);
    }

    // 1️⃣ Find reaction of a user on a post
    public function findUserReaction(Post $post, User $user): ?PostReaction
    {
        return $this->findOneBy([
            'idPost' => $post,
            'user' => $user,
        ]);
    }

    // 2️⃣ Count total reactions of a post
    public function countByPost(Post $post): int
    {
        return $this->count([
            'idPost' => $post,
        ]);
    }

    // 3️⃣ Count reactions grouped (LIKE, LOVE, etc)
    public function countGroupedByReaction(Post $post): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.reaction as reaction, COUNT(r.user) as total')
            ->where('r.idPost = :post')
            ->setParameter('post', $post)
            ->groupBy('r.reaction')
            ->getQuery()
            ->getArrayResult();

        // Default values (important)
        $result = [
            'LIKE' => 0,
            'LOVE' => 0,
            'HAHA' => 0,
            'WOW' => 0,
            'SAD' => 0,
            'ANGRY' => 0,
        ];

        foreach ($rows as $row) {
            $result[$row['reaction']] = (int) $row['total'];
        }

        return $result;
    }
}