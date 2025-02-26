<?php

namespace App\Form;

use App\Entity\Realisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class RealisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la réalisation',
                'attr' => [
                    'placeholder' => 'Entrez le titre de votre réalisation',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => [
                    'placeholder' => 'Décrivez en détail votre réalisation',
                    'class' => 'form-control',
                    'rows' => 10
                ]
            ])
            ->add('clientName', TextType::class, [
                'label' => 'Nom du client',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom du client (optionnel)',
                    'class' => 'form-control'
                ]
            ])
            ->add('projectDate', DateType::class, [
                'label' => 'Date du projet',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('projectType', ChoiceType::class, [
                'label' => 'Type de projet',
                'choices' => [
                    'Web' => 'web',
                    'Mobile' => 'mobile',
                    'Design' => 'design',
                    'Marketing' => 'marketing',
                    'Autre' => 'other'
                ],
                'placeholder' => 'Sélectionnez un type de projet',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('technologiesUsed', ChoiceType::class, [
                'label' => 'Technologies utilisées',
                'choices' => [
                    'PHP' => 'php',
                    'Symfony' => 'symfony',
                    'JavaScript' => 'javascript',
                    'React' => 'react',
                    'Vue.js' => 'vuejs',
                    'Node.js' => 'nodejs',
                    'Python' => 'python',
                    'Java' => 'java',
                    'Autre' => 'other'
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ])
            ->add('featuredImage', FileType::class, [
                'label' => 'Image principale',
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Realisation::class,
        ]);
    }
}
