<?php

namespace App\Controller\Admin;

use App\Entity\Gallery;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;

class GalleryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Gallery::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title')->setLabel('Titre'),
            SlugField::new('slug')
                ->setTargetFieldName('title')
                ->hideOnIndex(),
            TextEditorField::new('description')
                ->hideOnIndex(),
            AssociationField::new('images')
                ->onlyOnDetail()
                ->setLabel('Images')
                ->setTemplatePath('admin/gallery/images.html.twig'),
            DateTimeField::new('createdAt')
                ->hideOnForm()
                ->setLabel('Créée le'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Galerie')
            ->setEntityLabelInPlural('Galeries')
            ->setPageTitle('index', 'Gestion des galeries')
            ->setPageTitle('new', 'Créer une galerie')
            ->setPageTitle('edit', fn (Gallery $gallery) => sprintf('Modifier la galerie <b>%s</b>', $gallery->getTitle()))
            ->setPageTitle('detail', fn (Gallery $gallery) => sprintf('Galerie <b>%s</b>', $gallery->getTitle()));
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Gallery) {
            return;
        }

        if ($entityInstance->getCreatedAt() === null) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}