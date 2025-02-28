<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $service = $options['service'] ?? null;

        $builder
            ->add('bookingDate', DateTimeType::class, [
                'label' => 'Date de réservation',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une date de réservation']),
                    new Callback([$this, 'validateFutureDate'])
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes supplémentaires',
                'required' => false,
                'attr' => ['rows' => 4]
            ])
        ;
    }

    public function validateFutureDate($value, ExecutionContextInterface $context)
    {
        // Ensure the booking date is in the future
        $now = new \DateTime();
        $now->setTime(0, 0, 0); // Reset time to midnight
        $bookingDate = clone $value;
        $bookingDate->setTime(0, 0, 0); // Reset time to midnight for fair comparison

        if ($bookingDate <= $now) {
            $context->buildViolation('La date de réservation doit être dans le futur')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'service' => null,
        ]);

        $resolver->setAllowedTypes('service', ['null', 'App\Entity\Service']);
    }
}
