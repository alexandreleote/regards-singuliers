<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
                'label' => 'Vous êtes',
                'choices' => [
                    'Un particulier' => Contact::TYPE_PARTICULIER,
                    'Un professionnel' => Contact::TYPE_PROFESSIONNEL,
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'class' => 'js-contact-type-selector',
                ],
                'label_attr' => [
                    'class' => 'radio-custom',
                ],
            ])
            ->add('civilite', ChoiceType::class, [
                'label' => 'Civilité',
                'choices' => [
                    'Monsieur' => Contact::CIVILITE_MONSIEUR,
                    'Madame' => Contact::CIVILITE_MADAME,
                ],
                'expanded' => true,
                'multiple' => false,
                'label_attr' => [
                    'class' => 'radio-custom',
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Votre nom',
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Votre prénom',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Votre adresse email',
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre numéro de téléphone (facultatif)',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'placeholder' => 'Décrivez votre projet ou votre besoin',
                    'rows' => 5,
                ],
            ]);

        // Ajouter dynamiquement les champs pour les professionnels
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $contact = $event->getData();
            $form = $event->getForm();

            // Si c'est un professionnel ou si c'est un nouveau contact (type non défini)
            if (!$contact || $contact->getType() === Contact::TYPE_PROFESSIONNEL || $contact->getType() === null) {
                $form->add('entreprise', TextType::class, [
                    'label' => 'Entreprise',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Nom de votre entreprise',
                        'class' => 'js-professional-field',
                    ],
                ]);
                $form->add('localisation', TextType::class, [
                    'label' => 'Localisation',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Ville, région ou pays',
                        'class' => 'js-professional-field',
                    ],
                ]);
            }
        });

        // Mettre à jour le formulaire lorsque le type change
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!isset($data['type'])) {
                return;
            }

            if ($data['type'] === Contact::TYPE_PROFESSIONNEL) {
                if (!$form->has('entreprise')) {
                    $form->add('entreprise', TextType::class, [
                        'label' => 'Entreprise',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Nom de votre entreprise',
                            'class' => 'js-professional-field',
                        ],
                    ]);
                }
                if (!$form->has('localisation')) {
                    $form->add('localisation', TextType::class, [
                        'label' => 'Localisation',
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Ville, région ou pays',
                            'class' => 'js-professional-field',
                        ],
                    ]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'attr' => [
                'novalidate' => 'novalidate', // Désactiver la validation HTML5 pour utiliser celle de Symfony
                'class' => 'contact-form',
            ],
        ]);
    }
}