<?php
// src/Controller/ArticleController.php
namespace App\Controller;

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
        
        // Récupérer l'article précédent et suivant (optionnel)
        $prev_article = $articleRepository->findPreviousArticle($article);
        $next_article = $articleRepository->findNextArticle($article);
        
        // Récupérer uniquement les commentaires approuvés
        $approvedComments = $commentRepository->findApprovedByArticle($article);
        
        return $this->render('article/show.html.twig', [
            'article' => $article,
            'prev_article' => $prev_article ?? null,
            'next_article' => $next_article ?? null,
            'commentForm' => $form->createView(),
            'approvedComments' => $approvedComments,
        ]);
    }
}