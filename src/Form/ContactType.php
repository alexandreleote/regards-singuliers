<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Particulier' => Contact::TYPE_PARTICULIER,
                    'Professionnel' => Contact::TYPE_PROFESSIONNEL,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'attr' => ['class' => 'contact-type-choice'],
            ])
            ->add('civilite', ChoiceType::class, [
                'choices' => [
                    'Monsieur' => Contact::CIVILITE_MONSIEUR,
                    'Madame' => Contact::CIVILITE_MADAME,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Civilité',
                'attr' => ['class' => 'civilite-choice'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Votre nom'],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Votre prénom'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'votre@email.com'],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '01 23 45 67 89'],
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'Entreprise',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom de votre entreprise',
                    'class' => 'entreprise-field',
                ],
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'required' => false,
                'attr' => ['placeholder' => 'Ville ou code postal'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre projet',
                'attr' => [
                    'placeholder' => 'Décrivez votre projet ou vos besoins...',
                    'rows' => 5,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
