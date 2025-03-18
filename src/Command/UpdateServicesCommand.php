<?php

namespace App\Command;

use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-services',
    description: 'Met à jour les services existants avec les nouveaux champs',
)]
class UpdateServicesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $serviceRepository = $this->entityManager->getRepository(Service::class);
        $services = $serviceRepository->findAll();

        $io->progressStart(count($services));

        foreach ($services as $service) {
            // Si le name n'est pas défini, utiliser le title
            if (!$service->getName()) {
                $service->setName($service->getTitle());
            }

            // Si isActive n'est pas défini, le mettre à true par défaut
            if ($service->isActive() === null) {
                $service->setIsActive(true);
            }

            $this->entityManager->persist($service);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success('Les services ont été mis à jour avec succès.');

        return Command::SUCCESS;
    }
} 