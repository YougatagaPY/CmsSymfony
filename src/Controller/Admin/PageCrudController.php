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
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class PageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title');
        yield SlugField::new('slug')
            ->setTargetFieldName('title')
            ->hideOnIndex();
            
        // Ajouter ces champs pour sélectionner une image ou une galerie
        yield AssociationField::new('featuredImage')
            ->setLabel('Image mise en avant')
            ->setHelp('Sélectionnez une image à afficher en haut de la page')
            ->setFormTypeOption('placeholder', 'Aucune image')
            ->hideOnIndex();
            
        yield AssociationField::new('gallery')
            ->setLabel('Galerie complète')
            ->setHelp('Sélectionnez une galerie à afficher sur cette page')
            ->setFormTypeOption('placeholder', 'Aucune galerie')
            ->hideOnIndex();
            
        yield TextEditorField::new('content');
        yield TextareaField::new('meta')
            ->hideOnIndex()
            ->setHelp('Description de la page pour le référencement (sera placée dans la balise <meta name="description">)');
        
        yield TextareaField::new('metaKeyword')
            ->hideOnIndex()
            ->setLabel('Mots-clés')
            ->setHelp('Mots-clés pour le référencement, séparés par des virgules (seront placés dans la balise <meta name="keywords">)');
        
        yield DateTimeField::new('createdAt')
            ->setFormTypeOption('disabled', true)
            ->hideOnForm()
            ->setLabel('Créé le');
            
        yield DateTimeField::new('updateAt')
            ->hideOnForm()
            ->setLabel('Dernière modification')
            ->hideOnIndex(function ($entity) {
                return $entity->getCreatedAt() == $entity->getUpdateAt();
            });
    }

    // Le reste du code reste inchangé
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page')
            ->setEntityLabelInPlural('Pages')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Page) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
            $entityInstance->setUpdateAt(new \DateTimeImmutable());
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Page) {
            $entityInstance->setUpdateAt(new \DateTimeImmutable());
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }
}