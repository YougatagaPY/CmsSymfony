<?php
// src/Controller/ArticleController.php
namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArticleController extends AbstractController
{
    #[Route('/article/{slug}', name: 'article_show')]
    public function show(
        string $slug,
        ArticleRepository $articleRepository, 
        CommentRepository $commentRepository,
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer l'article
        $article = $articleRepository->findOneBy(['slug' => $slug]);
        
        if (!$article) {
            throw new NotFoundHttpException('Article non trouvé');
        }
        
        // Vérifier si l'article est publié ou si l'utilisateur est l'auteur ou un admin
        if (!$article->isPublished()) {
            // Si l'utilisateur n'est pas connecté ou n'est ni l'auteur ni un admin, on renvoie une 404
            if (!$this->getUser() || 
                ($this->getUser() !== $article->getAuthor() && !$this->isGranted('ROLE_ADMIN'))) {
                throw new NotFoundHttpException('Article non trouvé');
            }
            
            // Ajouter un message flash si l'article n'est pas publié
            $statusMessages = [
                Article::STATUS_PENDING => 'Cet article est en attente de validation.',
                Article::STATUS_REJECTED => 'Cet article a été rejeté.'
            ];
            
            if (isset($statusMessages[$article->getStatus()])) {
                $this->addFlash('warning', $statusMessages[$article->getStatus()]);
            }
        }
        
        // Créer un nouveau commentaire
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment, [
            'require_author' => false // On n'a pas besoin de demander l'auteur
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'utilisateur est connecté
            if (!$this->getUser()) {
                $this->addFlash('danger', 'Vous devez être connecté pour commenter.');
                return $this->redirectToRoute('app_login');
            }
            
            $comment->setArticle($article);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setStatus(Comment::STATUS_PENDING);
            
            // Associer l'utilisateur et son nom au commentaire
            /** @var User $user */
            $user = $this->getUser();
            $comment->setUser($user); // Nouvelle relation directe avec User
            $comment->setAuthor($user->getNom() ?: $user->getEmail()); // Garder pour compatibilité
            
            $entityManager->persist($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre commentaire a été soumis et sera visible après validation.');
            return $this->redirectToRoute('article_show', ['slug' => $article->getSlug()]);
        }
        
        // Récupérer l'article précédent et suivant (uniquement parmi les articles publiés)
        // Si l'article courant n'est pas publié, prev_article et next_article seront null
        $prev_article = $article->isPublished() ? $articleRepository->findPreviousArticle($article) : null;
        $next_article = $article->isPublished() ? $articleRepository->findNextArticle($article) : null;
        
        // Récupérer uniquement les commentaires approuvés
        $approvedComments = $commentRepository->findApprovedByArticle($article);
        
        return $this->render('article/show.html.twig', [
            'article' => $article,
            'prev_article' => $prev_article,
            'next_article' => $next_article,
            'commentForm' => $form->createView(),
            'approvedComments' => $approvedComments,
        ]);
    }
}