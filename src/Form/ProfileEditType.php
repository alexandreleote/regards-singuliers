<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr'=> [
                    'aria-label' => 'Votre prénom',
                    'placeholder' => 'Prénom',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre prénom',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Votre prénom doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Votre prénom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr'=> [
                    'aria-label' => 'Votre nom',
                    'placeholder' => 'Nom de famille',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre nom',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Votre nom doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Votre nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'=> [
                    'aria-label' => 'Votre adresse email',
                    'placeholder' => 'Votre adresse@email.fr'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer votre adresse email',
                    ]),
                ],
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'attr'=> [
                    'aria-label' => 'Votre numéro de téléphone',
                    'placeholder' => 'Ex : 06 12 34 56 78',
                    'pattern' => '[+()0-9 ]*',
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'max' => 15,
                        'minMessage' => 'Votre numéro de téléphone doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Votre numéro de téléphone ne peut pas dépasser {{ limit }} caractères',
                    ]),
                    new Regex([
                        'pattern' => '/^[+()0-9 ]+$/',
                        'message' => 'Veuillez saisir un numéro de téléphone valide'
                    ])
                ],
            ])
            ->add('streetNumber', TextType::class, [
                'label' => 'Numéro',
                'attr'=> [
                    'aria-label' => 'Votre numéro de rue',
                    'placeholder' => 'Ex : 1 Bis'
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 5,
                        'maxMessage' => 'Votre numéro ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('streetName', TextType::class, [
                'label' => 'Adresse',
                'attr'=> [
                    'aria-label' => 'Votre nom de rue',
                    'placeholder' => 'Ex : Rue de la Victoire'
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Votre adresse ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr'=> [
                    'aria-label' => 'Votre ville de résidence',
                    'placeholder' => 'Ex : Quimper'
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Votre ville ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('zip', TextType::class, [
                'label' => 'Code Postal',
                'attr'=> [
                    'aria-label' => 'Votre code postal',
                    'placeholder' => 'Ex : 29000'
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 15,
                        'maxMessage' => 'Votre code postal ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('region', TextType::class, [
                'label' => 'Région',
                'attr'=> [
                    'aria-label' => 'Votre région',
                    'placeholder' => 'Ex : Bretagne'
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Votre région ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'attr'=> [
                    'aria-label' => 'Votre mot de passe actuel',
                    'placeholder' => 'Mot de passe actuel'
                ],
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'attr'=> [
                    'aria-label' => 'Votre nouveau mot de passe',
                    'placeholder' => 'Nouveau mot de passe'
                ],
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirmer le nouveau mot de passe',
                'attr'=> [
                    'aria-label' => 'Confirmer votre nouveau mot de passe',
                    'placeholder' => 'Confirmer le nouveau mot de passe'
                ],
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
} 