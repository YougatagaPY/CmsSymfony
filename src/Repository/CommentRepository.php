<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Récupère les commentaires approuvés pour un article donné
     */
    public function findApprovedByArticle(Article $article)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.article = :article')
            ->andWhere('c.status = :status')
            ->setParameter('article', $article)
            ->setParameter('status', Comment::STATUS_APPROVED)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de commentaires en attente
     */
    public function countPendingComments(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.status = :status')
            ->setParameter('status', Comment::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }
}