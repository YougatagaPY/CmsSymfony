<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Image::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('filename')->setLabel('Nom du fichier'),
            TextField::new('caption')->setLabel('Légende'),
            AssociationField::new('gallery')->setLabel('Galerie')
                ->setFormTypeOption('placeholder', 'Sélectionnez une galerie'),
            Field::new('imageFile')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'mapped' => true,
                    'required' => false,
                    'attr' => [
                        'accept' => 'image/*',
                    ],
                ])
                ->setLabel('Fichier image')
                ->onlyOnForms(),
            ImageField::new('image')
                ->setBasePath('')
                ->setLabel('Aperçu')
                ->onlyOnDetail(),
            DateTimeField::new('createdAt')->hideOnForm()->setLabel('Ajoutée le'),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Image) {
            return;
        }

        $this->handleImageUpload($entityInstance);
        $entityInstance->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Image) {
            return;
        }

        $this->handleImageUpload($entityInstance);

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    private function handleImageUpload(Image $image): void
    {
        $imageFile = $image->getImageFile();

        if ($imageFile instanceof UploadedFile) {
            // Définir le nom du fichier à partir du fichier original
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
            
            $image->setFilename($newFilename);
            
            // Convertir l'image en base64
            $imageData = file_get_contents($imageFile->getRealPath());
            $base64Image = base64_encode($imageData);
            $mimeType = $imageFile->getMimeType();
            $image->setImage("data:{$mimeType};base64,{$base64Image}");
            
            // Réinitialiser le champ imageFile après traitement
            $image->setImageFile(null);
        }
    }
}