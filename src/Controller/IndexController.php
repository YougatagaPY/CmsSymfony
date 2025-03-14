<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository, PageRepository $pageRepository): Response
    {
        // Récupérer tous les articles
        $articles = $articleRepository->findAllArticles();
        
        // Récupérer l'article en vedette (le plus récent publié)
        $featuredArticle = $articleRepository->findOneBy(['status' => 'published'], ['createdAt' => 'DESC']);
        
        // Récupérer les pages récemment publiées
        $recentPages = $pageRepository->findBy(
            ['status' => 'published'],
            ['createdAt' => 'DESC'],
            5 // Limiter à 5 pages récentes
        );

        return $this->render('index/index.html.twig', [
            'articles' => $articles,
            'featured_article' => $featuredArticle,
            'recent_pages' => $recentPages,
        ]);
    }
}