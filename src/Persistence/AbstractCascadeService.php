<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\EntityRepositoryInterface;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
abstract class AbstractCascadeService
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var array<string, mixed>
     */
    protected array $options = [
        EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED,
        EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
        EntityEventOption::CLEAR_MODE       => ClearMode::DISABLED,
    ];

    /**
     * @var array<string, mixed>
     */
    protected array $collectionOptions = [
        EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED,
        EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
        EntityEventOption::CLEAR_MODE       => ClearMode::DISABLED,
    ];

    /**
     * @param LoggerInterface $logger
     * @param array<mixed>    $options
     * @param array<mixed>    $collectionOptions
     */
    public function __construct(LoggerInterface $logger, array $options = [], array $collectionOptions = [])
    {
        $this->logger = $logger;
        $this->options = empty($options) ? $this->options : $options;
        $this->collectionOptions = empty($collectionOptions) ? $this->collectionOptions : $collectionOptions;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param class-string           $entityName
     *
     * @return EntityRepositoryInterface
     * @throws PersistenceException
     * @todo We should implement a way to decorate the call the getRepository() with a concrete implementation
     *       of the EntityRepositoryProviderInterface
     */
    protected function getTargetRepository(
        EntityManagerInterface $entityManager,
        string $entityName
    ): EntityRepositoryInterface {
        if (!class_exists($entityName, true) && !$entityManager->getMetadataFactory()->hasMetadataFor($entityName)) {
            $errorMessage = sprintf('The target repository class \'%s\' could not be found', $entityName);

            $this->logger->error($errorMessage, ['entity_name' => $entityName]);

            throw new PersistenceException($errorMessage);
        }

        try {
            /** @var EntityRepositoryInterface|object|null $targetRepository */
            $targetRepository = $entityManager->getRepository($entityName);
        } catch (\Exception $e) {
            $errorMessage = sprintf(
                'An error occurred while attempting to load the repository for entity class \'%s\' : %s',
                $entityName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage, ['exception' => $e]);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }

        if (!isset($targetRepository) || !($targetRepository instanceof EntityRepositoryInterface)) {
            $errorMessage = sprintf(
                'The entity repository must be an object of type \'%s\'; \'%s\' returned in \'%s::%s\'',
                EntityRepositoryInterface::class,
                (is_object($targetRepository) ? get_class($targetRepository) : gettype($targetRepository)),
                static::class,
                __FUNCTION__
            );

            $this->logger->error($errorMessage, ['entity_name' => $entityName]);

            throw new PersistenceException($errorMessage);
        }

        return $targetRepository;
    }

    /**
     * @param EntityInterface                $sourceEntity
     * @param string                         $fieldName
     * @param ClassMetadata<EntityInterface> $sourceMetadata
     * @param ClassMetadata<EntityInterface> $targetMetadata
     *
     * @return EntityInterface|EntityInterface[]|iterable
     *
     * @throws PersistenceException
     */
    protected function resolveTargetEntityOrCollection(
        EntityInterface $sourceEntity,
        string $fieldName,
        ClassMetadata $sourceMetadata,
        ClassMetadata $targetMetadata
    ) {
        $methodName = 'get' . ucfirst($fieldName);

        if (!method_exists($sourceEntity, $methodName)) {
            $errorMessage = sprintf(
                'Failed to find required entity method \'%s::%s\'. The method is required for cascade operations '
                . 'of field \'%s\' of target entity \'%s\'',
                $sourceMetadata->getName(),
                $methodName,
                $fieldName,
                $targetMetadata->getName()
            );

            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        try {
            $targetEntityOrCollection = $sourceEntity->{$methodName}();
        } catch (\Exception $e) {
            $errorMessage = sprintf(
                'The call to resolve entity of type \'%s\' from method call \'%s::%s\' failed: %s',
                $targetMetadata->getName(),
                $sourceMetadata->getName(),
                $methodName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage, ['exception' => $e]);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }

        return $targetEntityOrCollection;
    }

    /**
     * @param iterable<EntityInterface>|EntityInterface|mixed|null $entityOrCollection
     * @param array<mixed>                                         $mapping
     *
     * @return bool
     */
    protected function isValidAssociation($entityOrCollection, array $mapping): bool
    {
        if (null === $entityOrCollection) {
            /**
             * @todo mapping class has a methods to fetch the id field mapping directly
             *
             * Note that we are hard coding the '0' key as the single field to use as the id/primary key.
             * If we implement EntityInterface correctly we will never have a composite key.
             */
            return isset($mapping['joinColumns'][0]['nullable']) && $mapping['joinColumns'][0]['nullable'];
        }

        return (is_iterable($entityOrCollection) || $entityOrCollection instanceof EntityInterface);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $entityName
     *
     * @return ClassMetadata<EntityInterface|object>
     *
     * @throws PersistenceException
     */
    protected function getClassMetadata(EntityManagerInterface $entityManager, string $entityName): ClassMetadata
    {
        try {
            return $entityManager->getClassMetadata($entityName);
        } catch (\Exception $e) {
            $errorMessage = sprintf(
                'The entity metadata mapping for class \'%s\' could not be loaded: %s',
                $entityName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage, ['exception' => $e, 'entity_name' => $entityName]);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }
}
