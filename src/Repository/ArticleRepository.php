<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return Article[] Returns all articles, ordered by the creation date (most recent first)
     */
    public function findAllArticles(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC') // Articles triés par la date de création (les plus récents d'abord)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Article[] Returns only published articles, ordered by the creation date (most recent first)
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', Article::STATUS_PUBLISHED)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'articles en attente de validation
     */
    public function countPendingArticles(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.status = :status')
            ->setParameter('status', Article::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve l'article précédent publié
     */
    public function findPreviousArticle($article)
    {
        // Si on veut seulement les articles publiés pour la navigation
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.id < :id')
            ->setParameter('status', Article::STATUS_PUBLISHED)
            ->setParameter('id', $article->getId())
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve l'article suivant publié
     */
    public function findNextArticle($article)
    {
        // Si on veut seulement les articles publiés pour la navigation
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.id > :id')
            ->setParameter('status', Article::STATUS_PUBLISHED)
            ->setParameter('id', $article->getId())
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Article[] Returns published articles filtered by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.category = :category')
            ->andWhere('a.status = :status')
            ->setParameter('category', $category)
            ->setParameter('status', Article::STATUS_PUBLISHED)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Article[] Returns articles by an author with optional status filter
     */
    public function findByAuthor(User $author, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.author = :author')
            ->setParameter('author', $author)
            ->orderBy('a.createdAt', 'DESC');
        
        if ($status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Article|null Returns a single article by its ID
     */
    public function findArticleById(int $id): ?Article
    {
        return $this->find($id);
    }

    /**
     * @return Article[] Returns published articles containing a specific search term in the content
     */
    public function findArticlesBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.content LIKE :searchTerm OR a.title LIKE :searchTerm')
            ->andWhere('a.status = :status')
            ->setParameter('searchTerm', '%'.$searchTerm.'%')
            ->setParameter('status', Article::STATUS_PUBLISHED)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}