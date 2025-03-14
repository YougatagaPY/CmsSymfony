<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ArticleCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            SlugField::new('slug')->setTargetFieldName('title'),
            TextEditorField::new('content', 'Contenu'),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail(),
            TextField::new('category', 'Catégorie'),
            Field::new('imageFile')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'mapped' => true,
                    'required' => false,
                ])
                ->setLabel('Image')
                ->hideOnIndex(),
        ];

        // Afficher le champ de statut seulement pour les admins ou en index
        if ($this->isGranted('ROLE_ADMIN') || $pageName === Crud::PAGE_INDEX) {
            $fields[] = ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => Article::STATUS_PENDING,
                    'Publié' => Article::STATUS_PUBLISHED,
                    'Rejeté' => Article::STATUS_REJECTED,
                ])
                ->renderAsBadges([
                    Article::STATUS_PENDING => 'warning',
                    Article::STATUS_PUBLISHED => 'success',
                    Article::STATUS_REJECTED => 'danger',
                ])
                ->setFormTypeOption('disabled', !$this->isGranted('ROLE_ADMIN'));
        }

        // Afficher l'auteur seulement en détail et en index
        if ($pageName === Crud::PAGE_DETAIL || $pageName === Crud::PAGE_INDEX) {
            $fields[] = AssociationField::new('author', 'Auteur');
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Action pour publier un article
        $publishAction = Action::new('publish', 'Publier')
            ->linkToCrudAction('publishArticle')
            ->displayIf(fn(Article $article) => 
                $article->getStatus() === Article::STATUS_PENDING && $this->isGranted('ROLE_ADMIN'))
            ->addCssClass('btn btn-success');

        // Action pour rejeter un article
        $rejectAction = Action::new('reject', 'Rejeter')
            ->linkToCrudAction('rejectArticle')
            ->displayIf(fn(Article $article) => 
                $article->getStatus() === Article::STATUS_PENDING && $this->isGranted('ROLE_ADMIN'))
            ->addCssClass('btn btn-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, $publishAction)
            ->add(Crud::PAGE_DETAIL, $publishAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('category')
            ->add('author')
            ->add('status')
            ->add('createdAt');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Gestion des articles')
            ->setPageTitle('edit', fn(Article $article) => 'Modifier : ' . $article->getTitle())
            ->setPageTitle('detail', fn(Article $article) => $article->getTitle());
    }

    /**
     * Action pour publier un article
     */
    public function publishArticle(AdminContext $context): RedirectResponse
    {
        /** @var Article $article */
        $article = $context->getEntity()->getInstance();
        
        $article->setStatus(Article::STATUS_PUBLISHED);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'L\'article a été publié.');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Action pour rejeter un article
     */
    public function rejectArticle(AdminContext $context): RedirectResponse
    {
        /** @var Article $article */
        $article = $context->getEntity()->getInstance();
        
        $article->setStatus(Article::STATUS_REJECTED);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'L\'article a été rejeté.');

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Article) {
            return;
        }

        $user = $this->security->getUser();
        if ($user) {
            $entityInstance->setAuthor($user);
        }

        // Définir le statut par défaut
        if ($this->isGranted('ROLE_ADMIN')) {
            $entityInstance->setStatus(Article::STATUS_PUBLISHED);
        } else {
            $entityInstance->setStatus(Article::STATUS_PENDING);
        }

        $this->handleImageUpload($entityInstance);

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Article) {
            return;
        }

        $this->handleImageUpload($entityInstance);

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    private function handleImageUpload(Article $article): void
    {
        $imageFile = $article->getImageFile();

        if ($imageFile instanceof UploadedFile) {
            $imageData = file_get_contents($imageFile->getRealPath());
            $base64Image = base64_encode($imageData);
            $mimeType = $imageFile->getMimeType();
            $article->setImage("data:{$mimeType};base64,{$base64Image}");
            $article->setImageFile(null);  // Clear the UploadedFile after processing
        }
    }
}