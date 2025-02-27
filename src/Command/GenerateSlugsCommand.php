<?php

namespace App\Command;

use App\Traits\SluggerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-slugs',
    description: 'Generate slugs for specified entity'
)]
class GenerateSlugsCommand extends Command
{
    use SluggerTrait;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'Fully qualified entity class name')
            ->setHelp('This command generates slugs for a specified entity. Use the full class name (e.g., App\Entity\Service).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityClass = $input->getArgument('entity');

        // Validate entity class
        if (!class_exists($entityClass)) {
            $io->error("Entity class $entityClass does not exist.");
            return Command::FAILURE;
        }

        // Get repository
        $repository = $this->entityManager->getRepository($entityClass);

        // Get all entities
        $entities = $repository->findAll();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($entities as $entity) {
            // Check if entity has title and slug methods
            if (!method_exists($entity, 'getTitle') || !method_exists($entity, 'setSlug') || !method_exists($entity, 'getSlug')) {
                $io->warning("Entity $entityClass does not have required methods.");
                return Command::FAILURE;
            }

            // If slug is already set, skip
            if ($entity->getSlug()) {
                $skippedCount++;
                continue;
            }

            // Generate unique slug
            $slug = $this->generateUniqueSlug(
                $entity->getTitle(), 
                function($proposedSlug) use ($repository) {
                    return $repository->findOneBy(['slug' => $proposedSlug]) !== null;
                }
            );

            $entity->setSlug($slug);
            $updatedCount++;
        }

        // Flush changes
        $this->entityManager->flush();

        $io->success(sprintf(
            'Slug generation complete for %s. Updated: %d entities, Skipped (already had slug): %d entities',
            $entityClass,
            $updatedCount,
            $skippedCount
        ));

        return Command::SUCCESS;
    }
}
