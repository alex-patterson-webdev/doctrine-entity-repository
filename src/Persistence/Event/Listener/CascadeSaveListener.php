<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\EntityRepositoryProviderInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class CascadeSaveListener
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
     *
     * @throws EntityRepositoryException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entityManager = $event->getEntityManager();
        $metadata = $entityManager->getClassMetadata($event->getEntityName());

        $entity = $event->getEntity();
        if (null === $entity) {
            return;
        }

        foreach ($metadata->getAssociationMappings() as $mapping) {
            // We only want to save associations that are configured to cascade
            if (!isset($mapping['isCascadePersist']) || true !== $mapping['isCascadePersist']) {
                continue;
            }

            if (!isset($mapping['targetEntityName'], $mapping['fieldName'], $mapping['type'])) {
                continue;
            }

            $targetEntity = $this->resolveTarget($mapping['fieldName'], $entity);
            if (null === $targetEntity) {
                // We were unable to resolve the referenced entity
                continue;
            }

            $targetRepository = $this->repositoryProvider->getEntityRepository($mapping['targetEntityName']);
            if (null === $targetRepository) {
                continue;
            }

            if (
                ClassMetadata::MANY_TO_ONE === $mapping['type']
                && $targetEntity instanceof $mapping['targetEntityName']
            ) {
                $targetRepository->save($targetEntity);
            }
        }
    }

    /**
     * @param string          $targetFieldName
     * @param EntityInterface $entity
     *
     * @return EntityInterface|EntityInterface[]|null
     */
    private function resolveTarget(string $targetFieldName, EntityInterface $entity)
    {
        $methodName = 'get' . ucfirst($targetFieldName);
        if (!method_exists($entity, $methodName)) {
            return null;
        }

        return $entity->{$methodName};
    }
}
