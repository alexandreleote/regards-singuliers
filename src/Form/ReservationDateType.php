<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReservationDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de la séance',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                ],
                'help' => 'Choisissez la date et l\'heure de votre séance conseil',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez choisir une date et une heure'
                    ]),
                    new GreaterThan([
                        'value' => new \DateTime(),
                        'message' => 'La date doit être dans le futur'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}