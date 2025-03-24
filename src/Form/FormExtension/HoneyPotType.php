<?php

namespace App\Form\FormExtension;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use App\EventSubscriber\HoneyPotSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BotIpRepository;

class HoneyPotType extends AbstractType
{
    private LoggerInterface $honeyPotlogger;
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private BotIpRepository $botIpRepository;

    public function __construct(
        LoggerInterface $honeyPotLogger,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        BotIpRepository $botIpRepository
    )
    {
        $this->honeyPotlogger = $honeyPotLogger;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->botIpRepository = $botIpRepository;
    }

    // On définit nos constantes servant à débusquer les bots
    protected const SPOTTING_HONEY_BOT = 'mobilePhone';

    protected const SPOTTING_HONEY_BOT_DOUBLE_CHECK = 'workEmail';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(self::SPOTTING_HONEY_BOT, TextType::class, $this->setHoneyPotFieldConfiguration())
                ->add(self::SPOTTING_HONEY_BOT_DOUBLE_CHECK, TextType::class, $this->setHoneyPotFieldConfiguration())
                ->addEventSubscriber(new HoneyPotSubscriber(
                    $this->honeyPotlogger,
                    $this->requestStack,
                    $this->entityManager,
                    $this->botIpRepository
                ));
    }

    // Tableau d'options passés sur les deux champs instanciés ci-dessus
    protected function setHoneyPotFieldConfiguration(): array
    {
        return [
            'attr'     => [
                'class'        => 'honeypot-field',
                'autocomplete' => 'off',
                'tabindex'     => '-1',
                'style'        => 'opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1; pointer-events: none;',
                'aria-hidden' => 'true'
            ],
            'mapped'   => false,
            'required' => false,
            'label' => false
        ];
    }

}