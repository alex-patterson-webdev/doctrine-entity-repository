<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\EntityRepositoryProviderInterface;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\Entity\EntityInterface;

class CascadeSaveListener
{
    /**
     * @var EntityRepositoryProviderInterface
     */
    private $repositoryProvider;

    /**
     * @param EntityRepositoryProviderInterface $repositoryProvider
     */
    public function __construct(EntityRepositoryProviderInterface $repositoryProvider)
    {
        $this->repositoryProvider = $repositoryProvider;
    }

    /**
     * @param EntityEvent $event
     */
    public function __invoke(EntityEvent $event)
    {
        $entityManager = $event->getEntityManager();
        $metadata = $entityManager->getClassMetadata($event->getEntityName());

        foreach ($metadata->getAssociationMappings() as $associationMapping) {

        }
    }

    /**
     * @param EntityInterface $entity
     */
    private function cascadeSave(EntityInterface $entity): void
    {

    }

    /**
     * @param iterable $collection
     */
    private function cascadeCollection(iterable $collection): void
    {

    }
}
