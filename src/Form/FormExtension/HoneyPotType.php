<?php

namespace App\Form\FormExtension;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use App\EventSubscriber\HoneyPotSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class HoneyPotType extends AbstractType
{
    private LoggerInterface $honeyPotlogger;

    private RequestStack $requestStack;

    public function __construct(
        LoggerInterface $honeyPotLogger,
        RequestStack $requestStack
    )
    {
     
        $this->honeyPotlogger = $honeyPotLogger;
        $this->requestStack = $requestStack;
    }

    // On définit nos constantes servant à débusquer les bots
    protected const SPOTTING_HONEY_BOT = 'mobilePhone';

    protected const SPOTTING_HONEY_BOT_DOUBLE_CHECK = 'workEmail';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(self::SPOTTING_HONEY_BOT, TextType::class, $this->setHoneyPotFieldConfiguration())
                ->add(self::SPOTTING_HONEY_BOT_DOUBLE_CHECK, TextType::class, $this->setHoneyPotFieldConfiguration())
                ->addEventSubscriber(new HoneyPotSubscriber($this->honeyPotlogger, $this->requestStack));
    }

    // Tableau d'options passés sur les deux champs instanciés ci-dessus
    protected function setHoneyPotFieldConfiguration(): array
    {
        return [
            'attr'     => [
                'class'        => 'form-sub',
                'autocomplete' => 'off',
                'tabindex'     => '-1' // On désactive la tabulation afin que l'utilisateur qui n'est pas un bot ne tombe pas sur les champs cachés
            ],
            'data'     => 'fake data :(', // A supprimer après les tests !
            'mapped'   => false,
            'required' => false    
        ];
    }

}