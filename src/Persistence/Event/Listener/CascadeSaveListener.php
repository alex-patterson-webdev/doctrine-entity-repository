<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\EntityRepositoryProviderInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $defaultSaveOptions = [];

    /**
     * @var array
     */
    private $defaultCollectionSaveOptions = [];

    /**
     * @param EntityRepositoryProviderInterface $repositoryProvider
     * @param LoggerInterface                   $logger
     * @param array                             $defaultSaveOptions
     * @param array                             $defaultCollectionSaveOptions
     */
    public function __construct(
        EntityRepositoryProviderInterface $repositoryProvider,
        LoggerInterface $logger,
        array $defaultSaveOptions,
        array $defaultCollectionSaveOptions = []
    ) {
        $this->repositoryProvider = $repositoryProvider;
        $this->logger = $logger;
        $this->defaultSaveOptions = $defaultSaveOptions;
        $this->defaultCollectionSaveOptions = $defaultCollectionSaveOptions;
    }

    /**
     * @param EntityEvent $event
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entity = $event->getEntity();
        if (null === $entity) {
            return;
        }

        $parameters = $event->getParameters();
        $cascadeMode = $parameters->getParam(EntityEventOption::CASCADE_MODE, CascadeMode::ALL);
        $entityName = $event->getEntityName();

        if (CascadeMode::ALL !== $cascadeMode && CascadeMode::SAVE !== $cascadeMode) {
            $this->logger->info(
                sprintf(
                    'Ignoring cascade save operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );
            return;
        }

        $saveOptions = array_replace_recursive(
            $this->defaultSaveOptions,
            $parameters->getParam(EntityEventOption::CASCADE_SAVE_OPTIONS, [])
        );

        $collectionSaveOptions = array_replace_recursive(
            $this->defaultCollectionSaveOptions,
            $parameters->getParam(EntityEventOption::CASCADE_SAVE_COLLECTION_OPTIONS, [])
        );

        $entityManager = $event->getEntityManager();
        $classMetadata = $this->getClassMetadata($entityName, $entityManager);

        foreach ($classMetadata->getAssociationMappings() as $mapping) {
            if (
                !isset(
                    $mapping['targetEntityName'],
                    $mapping['fieldName'],
                    $mapping['type'],
                    $mapping['isCascadePersist']
                )
                || true !== $mapping['isCascadePersist']
            ) {
                // We only want to save associations that are configured to cascade persist
                continue;
            }

            $targetEntityOrCollection = $this->resolveTargetEntityOrCollection(
                $entity,
                $mapping['fieldName'],
                $classMetadata,
                $this->getClassMetadata($mapping['targetEntityName'], $entityManager)
            );

            if (!$this->isValidAssociation($targetEntityOrCollection, $classMetadata, $mapping['fieldName'])) {
                continue;
            }

            $this->saveAssociation(
                $mapping['targetEntityName'],
                $mapping['type'],
                $targetEntityOrCollection,
                (is_iterable($targetEntityOrCollection) ? $collectionSaveOptions : $saveOptions)
            );
        }
    }

    /**
     * @param EntityInterface|EntityInterface[]|iterable|null $entityOrCollection
     * @param ClassMetadata                                   $classMetadata
     * @param string                                          $fieldName
     *
     * @return bool
     *
     * @throws PersistenceException
     */
    private function isValidAssociation($entityOrCollection, ClassMetadata $classMetadata, string $fieldName): bool
    {
        $associationMapping = $this->getAssociationMapping($classMetadata, $fieldName);
        $fieldMapping = $this->getFieldMapping($classMetadata, $fieldName);

        $test = 1;

        return true;
    }

    /**
     * @param string                                     $targetEntityName
     * @param string                                     $associationType
     * @param EntityInterface|EntityInterface[]|iterable $entityOrCollection
     * @param array                                      $saveOptions
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    private function saveAssociation(
        string $targetEntityName,
        string $associationType,
        $entityOrCollection,
        array $saveOptions = []
    ): void {
        $targetRepository = $this->repositoryProvider->getEntityRepository($targetEntityName);

        if (null === $targetRepository) {
            $errorMessage = sprintf(
                'Unable to perform cascade save operation for entity of type \'%s\': '
                . 'The entity repository could not be found',
                $targetEntityName
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        if (
            ClassMetadata::MANY_TO_ONE === $associationType
            && $entityOrCollection instanceof $targetEntityName
        ) {
            $targetRepository->save($entityOrCollection, $saveOptions);
        } elseif (
            ClassMetadata::ONE_TO_MANY === $associationType
            && (is_iterable($entityOrCollection))
        ) {
            $targetRepository->saveCollection($entityOrCollection, $saveOptions);
        }
    }

    /**
     * @param EntityInterface $sourceEntity
     * @param string          $fieldName
     * @param ClassMetadata   $sourceMetadata
     * @param ClassMetadata   $targetMetadata
     *
     * @return EntityInterface|EntityInterface[]|iterable
     *
     * @throws PersistenceException
     */
    private function resolveTargetEntityOrCollection(
        EntityInterface $sourceEntity,
        string $fieldName,
        ClassMetadata $sourceMetadata,
        ClassMetadata $targetMetadata
    ) {
        $methodName = 'get' . ucfirst($fieldName);

        if (!method_exists($sourceEntity, $methodName)) {
            $errorMessage = sprintf(
                'Failed to find required entity method \'%s::%s\'. The method is required for cascade save operations '
                . 'of field \'%s\' for target entity \'%s\'',
                $sourceMetadata->getName(),
                $methodName,
                $fieldName,
                $targetMetadata->getName()
            );

            throw new PersistenceException($errorMessage);
        }

        try {
            $targetEntityOrCollection = $sourceEntity->{$methodName}();
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'The call to resolve entity of type \'%s\' from method call \'%s::%s\' failed: %s',
                $targetMetadata->getName(),
                $sourceMetadata->getName(),
                $methodName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }

        return $targetEntityOrCollection;
    }

    /**
     * @param string                 $entityName
     * @param EntityManagerInterface $entityManager
     *
     * @return ClassMetadata
     *
     * @throws PersistenceException
     */
    private function getClassMetadata(string $entityName, EntityManagerInterface $entityManager): ClassMetadata
    {
        try {
            return $entityManager->getClassMetadata($entityName);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'The entity metadata mapping for class \'%s\' could not be loaded: %s',
                $entityName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return array
     *
     * @throws PersistenceException
     */
    private function getFieldMapping(ClassMetadata $metadata, string $fieldName): array
    {
        try {
            return $metadata->getFieldMapping($fieldName);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Failed to load field mapping information for field \'%s::%s\': %s',
                $metadata->getName(),
                $fieldName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return array
     *
     * @throws PersistenceException
     */
    private function getAssociationMapping(ClassMetadata $metadata, string $fieldName): array
    {
        try {
            return $metadata->getAssociationMapping($fieldName);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Failed to load association mapping information for field \'%s::%s\': %s',
                $metadata->getName(),
                $fieldName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }
}
