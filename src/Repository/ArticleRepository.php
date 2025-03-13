<?php

namespace App\Repository;

use App\Entity\Article;
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

    // Dans src/Repository/ArticleRepository.php

    public function findPreviousArticle($article)
    {
        return $this->createQueryBuilder('a')
            ->where('a.id < :id')
            ->setParameter('id', $article->getId())
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNextArticle($article)
    {
        return $this->createQueryBuilder('a')
            ->where('a.id > :id')
            ->setParameter('id', $article->getId())
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Article[] Returns articles filtered by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.category = :category')
            ->setParameter('category', $category)
            ->orderBy('a.createdAt', 'DESC') // Tri par date de création
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Article|null Returns a single article by its ID
     */
    public function findArticleById(int $id): ?Article
    {
        return $this->find($id); // Utilisation de la méthode `find` pour récupérer un article par son ID
    }

    /**
     * @return Article[] Returns articles containing a specific search term in the content
     */
    public function findArticlesBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.content LIKE :searchTerm OR a.title LIKE :searchTerm')
            ->setParameter('searchTerm', '%'.$searchTerm.'%')
            ->orderBy('a.createdAt', 'DESC') // Tri par date de création
            ->getQuery()
            ->getResult();
    }
}
