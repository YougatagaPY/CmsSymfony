<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\SecurityBundle\Security;

class CommentCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator,
        private Security $security
    ) {}

    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('article')->setFormTypeOption('disabled', true),
            AssociationField::new('user')->setFormTypeOption('disabled', true),
            TextField::new('author')->setFormTypeOption('disabled', true),
            TextEditorField::new('content')->setFormTypeOption('disabled', true),
            DateTimeField::new('createdAt')->setFormTypeOption('disabled', true),
            ChoiceField::new('status')
                ->setChoices([
                    'En attente' => Comment::STATUS_PENDING,
                    'Approuvé' => Comment::STATUS_APPROVED,
                    'Rejeté' => Comment::STATUS_REJECTED,
                ])
                ->renderAsBadges([
                    Comment::STATUS_PENDING => 'warning',
                    Comment::STATUS_APPROVED => 'success',
                    Comment::STATUS_REJECTED => 'danger',
                ]),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approve', 'Approuver')
            ->linkToCrudAction('approveComment')
            ->displayIf(function(Comment $comment) {
                return $this->security->isGranted('ROLE_ADMIN') && $comment->getStatus() !== Comment::STATUS_APPROVED;
            })
            ->addCssClass('btn btn-success');

        $rejectAction = Action::new('reject', 'Rejeter')
            ->linkToCrudAction('rejectComment')
            ->displayIf(function(Comment $comment) {
                return $this->security->isGranted('ROLE_ADMIN') && $comment->getStatus() !== Comment::STATUS_REJECTED;
            })
            ->addCssClass('btn btn-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('article')
            ->add('status')
            ->add('createdAt');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Gestion des commentaires')
            ->setPageTitle('edit', fn(Comment $comment) => 'Modifier le commentaire de ' . $comment->getAuthor())
            ->setPageTitle('detail', fn(Comment $comment) => 'Commentaire de ' . $comment->getAuthor());
    }

    /**
     * Action pour approuver un commentaire
     */
    public function approveComment(AdminContext $context): RedirectResponse
    {
        // Vérifier que seul l'admin peut exécuter cette action
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Seuls les administrateurs peuvent approuver les commentaires.');
            
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
                
            return $this->redirect($url);
        }
        
        /** @var Comment $comment */
        $comment = $context->getEntity()->getInstance();
        
        $comment->setStatus(Comment::STATUS_APPROVED);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le commentaire a été approuvé.');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Action pour rejeter un commentaire
     */
    public function rejectComment(AdminContext $context): RedirectResponse
    {
        // Vérifier que seul l'admin peut exécuter cette action
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Seuls les administrateurs peuvent rejeter les commentaires.');
            
            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
                
            return $this->redirect($url);
        }
        
        /** @var Comment $comment */
        $comment = $context->getEntity()->getInstance();
        
        $comment->setStatus(Comment::STATUS_REJECTED);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Le commentaire a été rejeté.');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}