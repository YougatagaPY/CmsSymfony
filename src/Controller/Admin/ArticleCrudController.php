<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ArticleCrudController extends AbstractCrudController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
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
                ->setLabel('Image'),
        ];
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
