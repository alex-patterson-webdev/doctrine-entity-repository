<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
class CascadeSaveService extends AbstractCascadeService
{
    /**
     * @param EntityManagerInterface   $entityManager
     * @param string                   $entityName
     * @param EntityInterface          $entity
     * @param array<string|int, mixed> $options
     * @param array<string|int, mixed> $collectionOptions
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function saveAssociations(
        EntityManagerInterface $entityManager,
        string $entityName,
        EntityInterface $entity,
        array $options = [],
        array $collectionOptions = []
    ): void {
        $options = array_replace_recursive($this->options, $options);
        $collectionOptions = array_replace_recursive($this->collectionOptions, $collectionOptions);

        $classMetadata = $this->getClassMetadata($entityManager, $entityName);
        $mappings = $classMetadata->getAssociationMappings();

        $this->logger->info(
            sprintf('Processing cascade save operations for for entity class \'%s\'', $entityName)
        );

        foreach ($mappings as $mapping) {
            if (
                !isset(
                    $mapping['targetEntity'],
                    $mapping['fieldName'],
                    $mapping['type'],
                    $mapping['isCascadePersist']
                )
                || true !== $mapping['isCascadePersist']
            ) {
                // We only want to save associations that are configured to cascade persist
                continue;
            }

            $this->logger->info(
                sprintf(
                    'The entity field \'%s::%s\' is configured for cascade operations for target entity \'%s\'',
                    $entityName,
                    $mapping['fieldName'],
                    $mapping['targetEntity']
                )
            );

            $targetEntityOrCollection = $this->resolveTargetEntityOrCollection(
                $entity,
                $mapping['fieldName'],
                $classMetadata,
                $this->getClassMetadata($entityManager, $mapping['targetEntity'])
            );

            if (!$this->isValidAssociation($targetEntityOrCollection, $mapping)) {
                $errorMessage = sprintf(
                    'The entity field \'%s::%s\' value could not be resolved',
                    $entityName,
                    $mapping['fieldName']
                );

                $this->logger->error($errorMessage);

                throw new PersistenceException($errorMessage);
            }

            $this->logger->info(
                sprintf(
                    'Performing cascading save operations for field \'%s::%s\'',
                    $entityName,
                    $mapping['fieldName']
                )
            );

            $this->saveAssociation(
                $entityManager,
                $mapping['targetEntity'],
                $targetEntityOrCollection,
                (is_iterable($targetEntityOrCollection) ? $collectionOptions : $options)
            );
        }
    }

    /**
     * @param EntityManagerInterface                          $entityManager
     * @param class-string                                    $entityName
     * @param EntityInterface|iterable<EntityInterface>|mixed $entityOrCollection
     * @param array<string|int, mixed>                        $options
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function saveAssociation(
        EntityManagerInterface $entityManager,
        string $entityName,
        $entityOrCollection,
        array $options = []
    ): void {
        if ($entityOrCollection instanceof EntityInterface) {
            $this->getTargetRepository($entityManager, $entityName)->save($entityOrCollection, $options);
        } elseif (is_iterable($entityOrCollection)) {
            $this->getTargetRepository($entityManager, $entityName)->saveCollection($entityOrCollection, $options);
        } else {
            $errorMessage = sprintf(
                'Unable to cascade save target entity \'%s\': The entity or collection is of an invalid type \'%s\'',
                $entityName,
                (is_object($entityOrCollection) ? get_class($entityOrCollection) : gettype($entityOrCollection))
            );

            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }
    }
}
