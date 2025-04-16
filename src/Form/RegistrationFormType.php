<?php

namespace App\Form;

use App\Entity\User;
use App\Form\FormExtension\HoneyPotType;
use Symfony\Component\Form\AbstractType;
use App\Service\AnonymizationService;
use App\Service\SecurityService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\RequestStack;

class RegistrationFormType extends AbstractType // Temporairement désactivé HoneyPotType
{
    public function __construct(
        private LoggerInterface $honeyPotLogger,
        private RequestStack $requestStack,
        private SecurityService $securityService,
        private AnonymizationService $anonymizationService
    ) {
        // Constructeur modifié pour ne plus appeler le parent
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Temporairement désactivé parent::buildForm($builder, $options);
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'aria-label' => 'Votre adresse email',
                    'placeholder' => 'Votre adresse@email.fr'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un email',
                    ]),
                    new Email([
                        'message' => 'Veuillez entrer un email valide',
                    ]),
                ],
                'label' => 'Email',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'attr' => [
                    'aria-label' => 'Accepter les termes d\'utilisation'
                ],
                'label' => 'Acceptez les termes',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les termes',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                        'aria-label' => 'Votre mot de passe',
                        'placeholder' => 'Mot de passe'
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Veuillez entrer un mot de passe',
                        ]),
                        new Length([
                            'min' => 12,
                            'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                        new Regex([
                            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{12,}$/',
                            'message' => 'Votre mot de passe doit contenir au minimum :
                            - 12 caractères
                            - Une majuscule
                            - Une minuscule
                            - Un chiffre
                            - Un caractère spécial (!@#$%^&*(),.?":{}|<>)'
                        ]),
                    ],
                    'label' => 'Mot de passe',
                ],
                'second_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                        'aria-label' => 'Confirmer votre mot de passe',
                        'placeholder' => 'Confirmer mot de passe'
                    ],
                    'label' => 'Confirmer le mot de passe',
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function validateRegistration(User $user): array
    {
        $errors = [];
        
        // Vérifier si l'IP est bannie
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $this->anonymizationService->isIpBanned($request->getClientIp())) {
            $errors[] = 'Cette adresse IP est associée à un compte banni. L\'inscription n\'est pas possible.';
        }

        return $errors;
    }
}
