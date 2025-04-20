<?php

namespace App\Form;

use App\Entity\Realisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\All;

class RealisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'aria-label' => 'Titre du projet réalisé'
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'form-control',
                    'aria-label' => 'Description du projet réalisé'
                ],
            ])
            ->add('mainImage', FileType::class, [
                'label' => 'Image principale',
                'attr' => [
                    'aria-label' => 'Image principale du projet'
                ],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('mainImageAlt', TextType::class, [
                'label' => 'Description de l\'image principale',
                'attr' => [
                    'class' => 'form-control',
                    'aria-label' => 'Description de l\'image principale pour l\'accessibilité',
                    'placeholder' => 'Décrivez l\'image principale'
                ],
                'required' => false,
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Images additionnelles',
                'attr' => [
                    'aria-label' => 'Images supplémentaires du projet sous forme de galerie d\'images.'
                ],
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '2M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/webp'
                                ],
                                'mimeTypesMessage' => 'Veuillez uploader des images valides (JPEG, PNG, WEBP)',
                            ])
                        ]
                    ])
                ],
            ])
            ->add('additionalImagesAlt', CollectionType::class, [
                'label' => 'Descriptions des images additionnelles',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'attr' => [
                        'class' => 'form-control',
                        'aria-label' => 'Description de l\'image pour l\'accessibilité',
                        'placeholder' => 'Décrivez cette image'
                    ],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Realisation::class,
        ]);
    }
}