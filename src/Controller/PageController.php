<?php

namespace App\Controller;

use App\Entity\Page;
use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends AbstractController
{
    #[Route('/page/{slug}', name: 'app_page_show')]
    public function show(string $slug, PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => $slug]);

        if (!$page) {
            throw $this->createNotFoundException('La page demandée n\'existe pas');
        }

        // Vérifier si la page est publiée ou si l'utilisateur est un admin
        if (!$page->isPublished()) {
            // Si l'utilisateur n'est pas connecté ou n'est pas admin, on renvoie une 404
            if (!$this->isGranted('ROLE_ADMIN')) {
                throw $this->createNotFoundException('La page demandée n\'existe pas');
            }
            
            // Ajouter un message flash si la page n'est pas publiée
            $statusMessages = [
                Page::STATUS_PENDING => 'Cette page est en attente de validation.',
                Page::STATUS_REJECTED => 'Cette page a été rejetée.'
            ];
            
            if (isset($statusMessages[$page->getStatus()])) {
                $this->addFlash('warning', $statusMessages[$page->getStatus()]);
            }
        }

        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }
}