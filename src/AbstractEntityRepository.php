<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Constant\QueryServiceOption;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Exception\EntityNotFoundException;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\DoctrineEntityRepository\Query\QueryServiceInterface;
use Arp\Entity\EntityInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository
 */
abstract class AbstractEntityRepository implements EntityRepositoryInterface
{
    /**
     * @var string
     */
    protected string $entityName;

    /**
     * @var QueryServiceInterface
     */
    protected QueryServiceInterface $queryService;

    /**
     * @var PersistServiceInterface
     */
    protected PersistServiceInterface $persistService;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param string                  $entityName
     * @param QueryServiceInterface   $queryService
     * @param PersistServiceInterface $persistService
     * @param LoggerInterface         $logger
     */
    public function __construct(
        string $entityName,
        QueryServiceInterface $queryService,
        PersistServiceInterface $persistService,
        LoggerInterface $logger
    ) {
        $this->entityName = $entityName;
        $this->queryService = $queryService;
        $this->persistService = $persistService;
        $this->logger = $logger;
    }

    /**
     * Return the fully qualified class name of the mapped entity instance.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->entityName;
    }

    /**
     * Return a single entity instance matching the provided $id.
     *
     * @param string $id
     *
     * @return EntityInterface|null
     *
     * @throws EntityRepositoryException
     */
    public function find($id): ?EntityInterface
    {
        try {
            return $this->queryService->findOneById($id);
        } catch (\Throwable $e) {
            $errorMessage = sprintf('Unable to find entity of type \'%s\': %s', $this->entityName, $e->getMessage());

            $this->logger->error($errorMessage, ['exception' => $e, 'id' => $id]);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Return a single entity instance matching the provided $criteria.
     *
     * @param array $criteria The entity filter criteria.
     *
     * @return EntityInterface|null
     *
     * @throws EntityRepositoryException
     */
    public function findOneBy(array $criteria): ?EntityInterface
    {
        try {
            return $this->queryService->findOne($criteria);
        } catch (\Throwable $e) {
            $errorMessage = sprintf('Unable to find entity of type \'%s\': %s', $this->entityName, $e->getMessage());

            $this->logger->error($errorMessage, ['exception' => $e, 'criteria' => $criteria]);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Return all of the entities within the collection.
     *
     * @return EntityInterface[]|iterable
     *
     * @throws EntityRepositoryException
     */
    public function findAll(): iterable
    {
        return $this->findBy([]);
    }

    /**
     * Return a collection of entities that match the provided $criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return EntityInterface[]|iterable
     *
     * @throws EntityRepositoryException
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): iterable
    {
        try {
            $options = [];

            if (null !== $orderBy) {
                $options[QueryServiceOption::ORDER_BY] = $orderBy;
            }

            if (null !== $limit) {
                $options[QueryServiceOption::LIMIT] = $limit;
            }

            if (null !== $offset) {
                $options[QueryServiceOption::OFFSET] = $offset;
            }

            return $this->queryService->findMany($criteria, $options);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Unable to return a collection of type \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e, 'criteria' => $criteria, 'options' => $options]);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Save a single entity instance.
     *
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @return EntityInterface
     *
     * @throws EntityRepositoryException
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface
    {
        try {
            return $this->persistService->save($entity, $options);
        } catch (\Throwable $e) {
            $errorMessage = sprintf('Unable to save entity of type \'%s\': %s', $this->entityName, $e->getMessage());

            $this->logger->error($errorMessage);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Save a collection of entities in a single transaction.
     *
     * @param iterable|EntityInterface[] $collection The collection of entities that should be saved.
     * @param array                      $options    the optional save options.
     *
     * @return iterable
     *
     * @throws EntityRepositoryException If the save cannot be completed
     */
    public function saveCollection(iterable $collection, array $options = []): iterable
    {
        $flushMode = $options[EntityEventOption::FLUSH_MODE] ?? FlushMode::ENABLED;
        $transactionMode = $options[EntityEventOption::TRANSACTION_MODE] ?? TransactionMode::ENABLED;

        try {
            if (TransactionMode::ENABLED === $transactionMode) {
                $this->persistService->beginTransaction();
            }

            $entities = [];
            $saveOptions = [
                EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
                EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED
            ];

            foreach ($collection as $entity) {
                $entities[] = $this->save($entity, $saveOptions);
            }

            if (FlushMode::ENABLED === $flushMode) {
                $this->persistService->flush();
            }
            if (TransactionMode::ENABLED === $transactionMode) {
                $this->persistService->commitTransaction();
            }

            return $entities;
        } catch (\Throwable $e) {
            if (TransactionMode::ENABLED === $transactionMode) {
                $this->persistService->rollbackTransaction();
            }

            $errorMessage = sprintf(
                'Unable to save collection of type \'%s\' : %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Delete an entity.
     *
     * @param EntityInterface|string $entity
     * @param array                  $options
     *
     * @return bool
     *
     * @throws EntityRepositoryException
     */
    public function delete($entity, array $options = []): bool
    {
        if (!is_string($entity) && !$entity instanceof EntityInterface) {
            $errorMessage = sprintf(
                'The \'entity\' argument must be a \'string\' or an object of type \'%s\'; '
                . '\'%s\' provided in \'%s::%s\'',
                EntityInterface::class,
                (is_object($entity) ? get_class($entity) : gettype($entity)),
                static::class,
                __FUNCTION__
            );

            $this->logger->error($errorMessage);

            throw new EntityRepositoryException($errorMessage);
        }

        if (is_string($entity)) {
            $id = $entity;
            $entity = $this->find($id);

            if (null === $entity) {
                $errorMessage = sprintf(
                    'Unable to delete entity \'%s::%s\': The entity could not be found',
                    $this->entityName,
                    $id
                );

                $this->logger->error($errorMessage);

                throw new EntityNotFoundException($errorMessage);
            }
        }

        try {
            return $this->persistService->delete($entity, $options);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Unable to delete entity of type \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e]);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Perform a deletion of a collection of entities.
     *
     * @param iterable|EntityInterface $collection
     * @param array                    $options
     *
     * @return int
     *
     * @throws EntityRepositoryException
     */
    public function deleteCollection(iterable $collection, array $options = []): int
    {
        $flushMode = $options[EntityEventOption::FLUSH_MODE] ?? FlushMode::ENABLED;
        $transactionMode = $options[EntityEventOption::TRANSACTION_MODE] ?? TransactionMode::ENABLED;

        try {
            if (TransactionMode::ENABLED === $transactionMode) {
                $this->persistService->beginTransaction();
            }

            $deleteOptions = [
                EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
                EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED,
            ];

            $deleted = 0;
            foreach ($collection as $entity) {
                if (true === $this->delete($entity, $deleteOptions)) {
                    $deleted++;
                }
            }

            if (FlushMode::ENABLED === $flushMode) {
                $this->persistService->flush();
            }

            if (TransactionMode::ENABLED === $transactionMode) {
                $this->persistService->commitTransaction();
            }

            return $deleted;
        } catch (\Throwable $e) {
            if (TransactionMode::ENABLED === $transactionMode) {
                $this->persistService->rollbackTransaction();
            }

            $errorMessage = sprintf(
                'Unable to delete collection of type \'%s\' : %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e]);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * @throws EntityRepositoryException
     */
    public function clear(): void
    {
        try {
            $this->persistService->clear();
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Unable to clear entity of type \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e]);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * @param EntityInterface $entity
     *
     * @throws EntityRepositoryException
     */
    public function refresh(EntityInterface $entity): void
    {
        try {
            $this->persistService->refresh($entity);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Unable to refresh entity of type \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e]);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }
}
