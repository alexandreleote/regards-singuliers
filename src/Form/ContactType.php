<?php

namespace App\Form;

use App\Entity\Contact;
use App\Form\FormExtension\HoneyPotType;
// use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactType extends HoneyPotType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options); // On fait appel au builder du parent pour ajouter des champs
        $builder
            ->add('individualOrProfessional', ChoiceType::class, [
                'choices' => [
                    'Individuel' => 'individuel',
                    'Professionnel' => 'professionnel',
                ],
                'label' => 'Vous êtes :',
                'attr'=> [
                    'aria-label' => 'Votre catégorie : particulier ou professionnel',
                ],
            ])
            ->add('civility', ChoiceType::class, [
                'choices' => [
                    'Madame' => 'Madame',
                    'Monsieur' => 'Monsieur',
                ],
                'label' => 'Civilité',
                'attr' => [
                    'aria-label' => 'Votre civilité'
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'aria-label' => 'Votre prénom',
                    'placeholder' => 'Votre prénom'
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'aria-label' => 'Votre nom de famille',
                    'placeholder' => 'Votre nom de famille'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'aria-label' => 'Votre adresse email',
                    'placeholder' => 'Votre adresse@email.fr'
                ],
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'aria-label' => 'Votre numéro de téléphone',
                    'placeholder' => 'Votre numéro de téléphone'
                ],
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'Entreprise',
                'attr' => [
                    'aria-label' => 'Nom de votre entreprise',
                    'placeholder' => 'Votre entreprise'
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'aria-label' => 'Description détaillée de votre projet (style souhaité, budget, contraintes particulières...)',
                    'placeholder' => 'Décrivez votre projet en quelques mots (style souhaité, budget, contraintes particulières...)'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'aria-label' => 'Envoyer le formulaire de contact',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
