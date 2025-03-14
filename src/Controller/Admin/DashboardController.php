<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Page;
use App\Entity\Article;
use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\Comment;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\GalleryRepository;
use App\Repository\ImageRepository;
use App\Repository\PageRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[Route('/admin', name: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private CommentRepository $commentRepository,
        private ArticleRepository $articleRepository,
        private UserRepository $userRepository,
        private GalleryRepository $galleryRepository,
        private ImageRepository $imageRepository,
        private PageRepository $pageRepository,
        private Security $security
    ) {}

    public function index(): Response
    {
        // Récupérer les statistiques
        $pendingComments = $this->commentRepository->countPendingComments();
        $articleCount = $this->articleRepository->count([]);
        $userCount = $this->userRepository->count([]);
        $galleryCount = $this->galleryRepository->count([]);
        $imageCount = $this->imageRepository->count([]);
        $pageCount = $this->pageRepository->count([]);
        
        // Récupérer les commentaires récents
        $recentComments = $this->commentRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Récupérer les articles récents
        $recentArticles = $this->articleRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        return $this->render('admin/dashboard.html.twig', [
            'pending_comments' => $pendingComments,
            'article_count' => $articleCount,
            'user_count' => $userCount,
            'gallery_count' => $galleryCount,
            'image_count' => $imageCount,
            'page_count' => $pageCount,
            'recent_comments' => $recentComments,
            'recent_articles' => $recentArticles,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('CMS Admin')
            ->setFaviconPath('favicon.svg');
    }

    public function configureMenuItems(): iterable
    {
        // Récupérer le nombre de commentaires en attente
        $pendingComments = $this->commentRepository->countPendingComments();
        
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        yield MenuItem::section('Contenu');
        yield MenuItem::linkToCrud('Articles', 'fa fa-newspaper', Article::class);
        yield MenuItem::linkToCrud('Pages', 'fa fa-file', Page::class);
        
        yield MenuItem::section('Médias');
        yield MenuItem::linkToCrud('Galleries', 'fa fa-images', Gallery::class);
        yield MenuItem::linkToCrud('Images', 'fa fa-image', Image::class);
        
        yield MenuItem::section('Interactions');
        yield MenuItem::linkToCrud('Commentaires', 'fa fa-comments', Comment::class)
            ->setBadge($pendingComments, $pendingComments > 0 ? 'warning' : 'secondary');
        
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        
        // Lien direct vers les commentaires en attente de modération
        if ($pendingComments > 0) {
            yield MenuItem::linkToUrl('Commentaires à modérer', 'fa fa-exclamation-circle', $this->adminUrlGenerator
                ->setController(CommentCrudController::class)
                ->setAction('index')
                ->set('filters[status][comparison]', '=')
                ->set('filters[status][value]', 'pending')
                ->generateUrl());
        }

        // Récupérer le nombre d'articles en attente de modération
        $pendingArticles = $this->articleRepository->countPendingArticles();

// Lien direct vers les articles en attente de modération
    if ($pendingArticles > 0) {
    yield MenuItem::linkToUrl('Articles à modérer', 'fa fa-exclamation-circle', $this->adminUrlGenerator
        ->setController(ArticleCrudController::class)
        ->setAction('index')
        ->set('filters[status][comparison]', '=')
        ->set('filters[status][value]', 'pending') // Assure-toi que le statut des articles en attente est bien "brouillon"
        ->generateUrl());
}


        
        
        yield MenuItem::section('');
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-external-link-alt', $this->generateUrl('app_home'))
            ->setLinkTarget('_blank');
    }
}