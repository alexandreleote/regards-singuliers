<?php

namespace App\Form\FormExtension;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use App\EventSubscriber\HoneyPotSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Service\SecurityService;

class HoneyPotType extends AbstractType
{
    private LoggerInterface $honeyPotlogger;
    private RequestStack $requestStack;
    private SecurityService $securityService;

    public function __construct(
        LoggerInterface $honeyPotLogger,
        RequestStack $requestStack,
        SecurityService $securityService
    ) {
        $this->honeyPotlogger = $honeyPotLogger;
        $this->requestStack = $requestStack;
        $this->securityService = $securityService;
    }

    // Noms de champs aléatoires pour le honeypot
    protected function getRandomFieldNames(): array
    {
        $attractiveFields = [
            ['firstName', 'first_name', 'prenom', 'given_name'],
            ['lastName', 'last_name', 'nom', 'family_name'],
            ['username', 'login', 'user_name', 'nickname'],
            ['phone', 'telephone', 'mobile', 'contact_phone'],
            ['address', 'location', 'street', 'city']
        ];

        // Choisir deux catégories différentes au hasard
        $categories = array_rand($attractiveFields, 2);
        $field1Category = $attractiveFields[$categories[0]];
        $field2Category = $attractiveFields[$categories[1]];

        // Choisir un nom aléatoire dans chaque catégorie et ajouter le préfixe honeypot_
        return [
            'field1' => 'honeypot_' . $field1Category[array_rand($field1Category)],
            'field2' => 'honeypot_' . $field2Category[array_rand($field2Category)]
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldNames = $this->getRandomFieldNames();
        $timestamp = time();
        
        $builder
            ->add($fieldNames['field1'], TextType::class, $this->setHoneyPotFieldConfiguration())
            ->add($fieldNames['field2'], TextType::class, $this->setHoneyPotFieldConfiguration())
            ->add('_timestamp', TextType::class, [
                'mapped' => false,
                'data' => $timestamp,
                'label' => false,
                'attr' => [
                    'style' => 'position: absolute; left: -9999px; top: -9999px; opacity: 0; height: 0; width: 0; padding: 0; margin: 0; border: 0;',
                    'readonly' => true,
                    'tabindex' => '-1',
                    'aria-hidden' => 'true'
                ]
            ])
            ->addEventSubscriber(new HoneyPotSubscriber(
                $this->honeyPotlogger,
                $this->requestStack,
                $this->securityService
            ));
    }

    // Tableau d'options passés sur les deux champs instanciés ci-dessus
    protected function setHoneyPotFieldConfiguration(): array
    {
        $randomClass = 'form-control input-' . bin2hex(random_bytes(3));
        return [
            'attr' => [
                'class' => $randomClass,
                'autocomplete' => 'off',
                'tabindex' => '-1',
                'style' => 'position: absolute; left: -9999px; top: -9999px; opacity: 0; height: 0; width: 0; padding: 0; margin: 0; border: 0;',
                'aria-hidden' => 'true',
                'data-optional' => 'true'
            ],
            'mapped' => false,
            'required' => false,
            'label' => false,
            'data' => '',
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\Length([
                    'max' => 0,
                    'maxMessage' => 'This field should be empty'
                ])
            ]
        ];
    }

    protected function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }
}