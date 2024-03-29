<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
class CascadeDeleteService extends AbstractCascadeService
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $entityName
     * @param EntityInterface        $entity
     * @param array<mixed>           $deleteOptions
     * @param array<mixed>           $deleteCollectionOptions
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function deleteAssociations(
        EntityManagerInterface $entityManager,
        string $entityName,
        EntityInterface $entity,
        array $deleteOptions = [],
        array $deleteCollectionOptions = []
    ): void {
        $deleteOptions = array_replace_recursive($this->options, $deleteOptions);
        $deleteCollectionOptions = array_replace_recursive($this->collectionOptions, $deleteCollectionOptions);

        /** @var ClassMetadata<EntityInterface> $classMetadata */
        $classMetadata = $this->getClassMetadata($entityManager, $entityName);

        /** @var array<string, mixed> $mappings */
        $mappings = $classMetadata->getAssociationMappings();

        $this->logger->info(
            sprintf('Processing cascade delete operations for for entity class \'%s\'', $entityName)
        );

        foreach ($mappings as $mapping) {
            if (
                !isset(
                    $mapping['targetEntity'],
                    $mapping['fieldName'],
                    $mapping['type'],
                    $mapping['isCascadeRemove']
                )
                || true !== $mapping['isCascadeRemove']
            ) {
                // We only want to save associations that are configured to cascade delete/remove
                continue;
            }

            $this->logger->info(
                sprintf(
                    'The entity field \'%s::%s\' is configured for cascade delete operations for target entity \'%s\'',
                    $entityName,
                    $mapping['fieldName'],
                    $mapping['targetEntity']
                )
            );

            /** @var ClassMetadata<EntityInterface> $targetMetadata */
            $targetMetadata = $this->getClassMetadata($entityManager, $mapping['targetEntity']);

            $targetEntityOrCollection = $this->resolveTargetEntityOrCollection(
                $entity,
                $mapping['fieldName'],
                $classMetadata,
                $targetMetadata
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
                    'Performing cascading delete operations for field \'%s::%s\'',
                    $entityName,
                    $mapping['fieldName']
                )
            );

            $this->deleteAssociation(
                $entityManager,
                $mapping['targetEntity'],
                $targetEntityOrCollection,
                (is_iterable($targetEntityOrCollection) ? $deleteCollectionOptions : $deleteOptions)
            );
        }
    }

    /**
     * @param EntityManagerInterface                          $entityManager
     * @param class-string                                    $targetEntityName
     * @param EntityInterface|iterable<EntityInterface>|mixed $entityOrCollection
     * @param array<mixed>                                    $options
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function deleteAssociation(
        EntityManagerInterface $entityManager,
        string $targetEntityName,
        $entityOrCollection,
        array $options = []
    ): void {
        $targetRepository = $this->getTargetRepository($entityManager, $targetEntityName);

        if (is_iterable($entityOrCollection)) {
            $targetRepository->deleteCollection($entityOrCollection, $options);
        } elseif ($entityOrCollection instanceof EntityInterface) {
            $targetRepository->delete($entityOrCollection, $options);
        } else {
            $errorMessage = sprintf(
                'Unable to cascade save target entity \'%s\': The entity or collection is of an invalid type \'%s\'',
                $targetEntityName,
                (is_object($entityOrCollection) ? get_class($entityOrCollection) : gettype($entityOrCollection))
            );

            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }
    }
}
