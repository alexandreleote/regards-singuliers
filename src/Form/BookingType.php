<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingDate', DateTimeType::class, [
                'label' => 'Date de réservation',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une date de réservation'])
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes supplémentaires',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
