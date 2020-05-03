<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\EntityRepositoryProviderInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
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

        foreach ($metadata->getAssociationMappings() as $associationMapping) {
            if (!isset($associationMapping['isCascadePersist']) || true !== $associationMapping['isCascadePersist']) {
                continue;
            }

            /** @var string $targetEntityName */
            $targetEntityName = $associationMapping['targetEntity'] ?? null;
            $fieldName = $associationMapping['fieldName'] ?? null;
            $associationType = $associationMapping['type'] ?? null;

            if (null === $targetEntityName || null === $associationType) {
                continue;
            }

            $targetRepository = $this->repositoryProvider->getEntityRepository($targetEntityName);
            if (null === $targetRepository) {
                continue;
            }

            $methodName = 'get' . ucfirst($fieldName);
            if ($associationType !== ClassMetadata::MANY_TO_ONE || !method_exists($entity, $methodName)) {
                continue;
            }

            $targetEntity = $entity->{$methodName};

            if ($targetEntity instanceof $targetEntityName) {
                $targetRepository->save($targetEntity);
            }
        }
    }
}
