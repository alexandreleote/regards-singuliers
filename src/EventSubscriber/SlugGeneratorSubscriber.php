<?php

namespace App\EventSubscriber;

use App\Traits\SluggerTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class SlugGeneratorSubscriber
{
    use SluggerTrait;

    /**
     * Liste des entités qui doivent générer un slug automatiquement
     */
    private array $sluggableEntities = [
        'App\Entity\Blog',
        'App\Entity\Service',
        // Ajoutez d'autres entités ici si nécessaire
    ];

    #[AsEntityListener(event: Events::prePersist, method: 'prePersist')]
    public function prePersist(object $entity, PrePersistEventArgs $event): void
    {
        $this->generateSlugIfNeeded($entity);
    }

    #[AsEntityListener(event: Events::preUpdate, method: 'preUpdate')]
    public function preUpdate(object $entity, PreUpdateEventArgs $event): void
    {
        $this->generateSlugIfNeeded($entity);
    }

    private function generateSlugIfNeeded(object $entity): void
    {
        // Vérifier si l'entité est dans la liste des entités qui nécessitent un slug
        if (!in_array(get_class($entity), $this->sluggableEntities)) {
            return;
        }

        // Vérifier que l'entité a les méthodes nécessaires
        if (!method_exists($entity, 'getTitle') || 
            !method_exists($entity, 'setSlug') || 
            !method_exists($entity, 'getSlug')) {
            return;
        }

        // Si un slug existe déjà, ne pas le remplacer
        if ($entity->getSlug()) {
            return;
        }

        // Générer un slug unique
        $slug = $this->generateUniqueSlug(
            $entity->getTitle(), 
            function($proposedSlug) use ($entity) {
                $repository = $this->getEntityManager()->getRepository(get_class($entity));
                return $repository->findOneBy(['slug' => $proposedSlug]) !== null;
            }
        );

        $entity->setSlug($slug);
    }

    private function getEntityManager()
    {
        return $GLOBALS['kernel']->getContainer()->get('doctrine.orm.entity_manager');
    }
}
