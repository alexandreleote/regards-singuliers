<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('individualOrProfessional', ChoiceType::class, [
                'choices' => [
                    'Particulier' => 'particulier',
                    'Professionnel' => 'professionnel'
                ],
                'label' => 'Vous êtes un',
                'expanded' => true,
                'multiple' => false,
                'autocomplete' => true,
                'required' => true,
            ])
            ->add('civility', ChoiceType::class, [
                'choices' => [
                    'Madame' => 'Mme',
                    'Monsieur' => 'M'
                ],
                'label' => 'Civilité',
                'expanded' => true,
                'multiple' => false,
                'autocomplete' => true,
                'required' => true
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Téléphone',
                'required' => true
            ])
            ->addDependent('entreprise', 'individualOrProfessional', function (DependentField $field, ?string $individualOrProfessional) {
                if ($individualOrProfessional === 'professionnel') {
                    $field->add(TextType::class, [
                        'label' => 'Entreprise',
                        'required' => false
                    ]);
                }
            })
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
