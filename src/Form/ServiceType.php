<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du service',
                'attr' => [
                    'placeholder' => 'Nom du service proposé',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du service',
                'attr' => [
                    'placeholder' => 'Décrivez votre service',
                    'class' => 'form-control',
                    'rows' => 8
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix du service',
                'required' => false,
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => 'Prix du service (optionnel)',
                    'class' => 'form-control'
                ]
            ])
            ->add('featuredImage', FileType::class, [
                'label' => 'Image de présentation',
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
            'data_class' => Service::class,
        ]);
    }
}
