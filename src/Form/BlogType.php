<?php

namespace App\Form;

use App\Entity\Blog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'article',
                'attr' => [
                    'placeholder' => 'Entrez le titre de votre article',
                    'class' => 'form-control'
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu de l\'article',
                'attr' => [
                    'placeholder' => 'Rédigez votre article ici',
                    'class' => 'form-control',
                    'rows' => 10
                ]
            ])
            ->add('metaDescription', TextType::class, [
                'label' => 'Meta description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description courte pour le référencement',
                    'class' => 'form-control',
                    'maxlength' => 160
                ]
            ])
            ->add('featuredImage', FileType::class, [
                'label' => 'Image à la une',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Merci de télécharger une image valide (JPEG, PNG, WebP, GIF)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control-file'
                ]
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publier immédiatement',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
        ]);
    }
}
