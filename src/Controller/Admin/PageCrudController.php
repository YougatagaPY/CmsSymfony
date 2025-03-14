<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PageCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('title'),
            SlugField::new('slug')
                ->setTargetFieldName('title')
                ->hideOnIndex(),
                
            // Ajouter ces champs pour sélectionner une image ou une galerie
            AssociationField::new('featuredImage')
                ->setLabel('Image mise en avant')
                ->setHelp('Sélectionnez une image à afficher en haut de la page')
                ->setFormTypeOption('placeholder', 'Aucune image')
                ->hideOnIndex(),
                
            AssociationField::new('gallery')
                ->setLabel('Galerie complète')
                ->setHelp('Sélectionnez une galerie à afficher sur cette page')
                ->setFormTypeOption('placeholder', 'Aucune galerie')
                ->hideOnIndex(),
                
            TextEditorField::new('content'),
            TextareaField::new('meta')
                ->hideOnIndex()
                ->setHelp('Description de la page pour le référencement (sera placée dans la balise <meta name="description">)'),
            
            TextareaField::new('metaKeyword')
                ->hideOnIndex()
                ->setLabel('Mots-clés')
                ->setHelp('Mots-clés pour le référencement, séparés par des virgules (seront placés dans la balise <meta name="keywords">)'),
            
            DateTimeField::new('createdAt')
                ->setFormTypeOption('disabled', true)
                ->hideOnForm()
                ->setLabel('Créé le'),
                
            DateTimeField::new('updateAt')
                ->hideOnForm()
                ->setLabel('Dernière modification')
                ->hideOnIndex(function ($entity) {
                    return $entity->getCreatedAt() == $entity->getUpdateAt();
                }),
        ];

        // Afficher le champ de statut seulement pour les admins ou en index
        if ($this->isGranted('ROLE_ADMIN') || $pageName === Crud::PAGE_INDEX) {
            $fields[] = ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => Page::STATUS_PENDING,
                    'Publié' => Page::STATUS_PUBLISHED,
                    'Rejeté' => Page::STATUS_REJECTED,
                ])
                ->renderAsBadges([
                    Page::STATUS_PENDING => 'warning',
                    Page::STATUS_PUBLISHED => 'success',
                    Page::STATUS_REJECTED => 'danger',
                ])
                ->setFormTypeOption('disabled', !$this->isGranted('ROLE_ADMIN'));
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action pour publier une page
        $publishAction = Action::new('publish', 'Publier')
            ->linkToCrudAction('publishPage')
            ->displayIf(fn(Page $page) => 
                $page->getStatus() !== Page::STATUS_PUBLISHED && $this->isGranted('ROLE_ADMIN'))
            ->addCssClass('btn btn-success');

        // Action pour mettre en attente une page
        $pendingAction = Action::new('pending', 'Mettre en attente')
            ->linkToCrudAction('pendingPage')
            ->displayIf(fn(Page $page) => 
                $page->getStatus() !== Page::STATUS_PENDING && $this->isGranted('ROLE_ADMIN'))
            ->addCssClass('btn btn-warning');

        // Action pour rejeter une page
        $rejectAction = Action::new('reject', 'Rejeter')
            ->linkToCrudAction('rejectPage')
            ->displayIf(fn(Page $page) => 
                $page->getStatus() !== Page::STATUS_REJECTED && $this->isGranted('ROLE_ADMIN'))
            ->addCssClass('btn btn-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, $publishAction)
            ->add(Crud::PAGE_DETAIL, $publishAction)
            ->add(Crud::PAGE_INDEX, $pendingAction)
            ->add(Crud::PAGE_DETAIL, $pendingAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('status')
            ->add('createdAt');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page')
            ->setEntityLabelInPlural('Pages')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    /**
     * Action pour publier une page
     */
    public function publishPage(AdminContext $context): RedirectResponse
    {
        /** @var Page $page */
        $page = $context->getEntity()->getInstance();
        
        $page->setStatus(Page::STATUS_PUBLISHED);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La page a été publiée.');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Action pour mettre une page en attente
     */
    public function pendingPage(AdminContext $context): RedirectResponse
    {
        /** @var Page $page */
        $page = $context->getEntity()->getInstance();
        
        $page->setStatus(Page::STATUS_PENDING);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La page a été mise en attente.');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Action pour rejeter une page
     */
    public function rejectPage(AdminContext $context): RedirectResponse
    {
        /** @var Page $page */
        $page = $context->getEntity()->getInstance();
        
        $page->setStatus(Page::STATUS_REJECTED);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'La page a été rejetée.');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Page) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
            $entityInstance->setUpdateAt(new \DateTimeImmutable());
            
            // Définir le statut par défaut en fonction du rôle
            if ($this->isGranted('ROLE_ADMIN')) {
                $entityInstance->setStatus(Page::STATUS_PUBLISHED);
            } else {
                $entityInstance->setStatus(Page::STATUS_PENDING);
            }
        }
        
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Page) {
            $entityInstance->setUpdateAt(new \DateTimeImmutable());
        }
        
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}