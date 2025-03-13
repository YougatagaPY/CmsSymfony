<?php
// src/Form/CommentType.php
namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // N'ajouter le champ auteur que si requis (pour l'admin)
        if ($options['require_author']) {
            $builder->add('author', TextType::class, [
                'label' => 'Votre nom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom',
                    ]),
                ],
            ]);
        }
        
        $builder->add('content', TextareaType::class, [
            'label' => 'Votre commentaire',
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez entrer votre commentaire',
                ]),
            ],
            'attr' => [
                'placeholder' => 'Partagez votre opinion...',
                'rows' => 5
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'require_author' => true,
        ]);
    }
}