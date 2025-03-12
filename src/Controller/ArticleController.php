<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAllArticles();

        return $this->render('index/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/article/{slug}', name: 'article_show')]
    public function show(string $slug, ArticleRepository $articleRepository): Response
    {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
}
