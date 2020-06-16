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
class CascadeDeleteService extends AbstractCascadeService
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $entityName
     * @param EntityInterface        $entity
     * @param array                  $deleteOptions
     * @param array                  $deleteCollectionOptions
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

        $classMetadata = $this->getClassMetadata($entityManager, $entityName);
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
                    $mappings['fieldName'],
                    $mappings['targetEntity']
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
                    $mappings['fieldName']
                );
                $this->logger->error($errorMessage);

                throw new PersistenceException($errorMessage);
                continue;
            }

            $this->logger->info(
                sprintf(
                    'Performing cascading delete operations for field \'%s::%s\'',
                    $entityName,
                    $mappings['fieldName']
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
     * @param EntityManagerInterface $entityManager
     * @param string                 $targetEntityName
     * @param                        $entityOrCollection
     * @param array                  $options
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
