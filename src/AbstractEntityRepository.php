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
    protected $entityName;

    /**
     * @var QueryServiceInterface
     */
    protected $queryService;

    /**
     * @var PersistServiceInterface
     */
    protected $persistService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

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

            $this->logger->error($errorMessage);

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

            $this->logger->error($errorMessage);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Return all of the entities within the collection.
     *
     * @return EntityInterface[]
     *
     * @throws EntityRepositoryException
     */
    public function findAll(): array
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
     * @return EntityInterface[]
     *
     * @throws EntityRepositoryException
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        try {
            $options = [
                QueryServiceOption::LIMIT    => $limit,
                QueryServiceOption::OFFSET   => $offset,
                QueryServiceOption::ORDER_BY => $orderBy,
            ];

            return $this->queryService->findMany($criteria, $options);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Unable to return a collection of type \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage);

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
        try {
            $saveOptions = [
                EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
                EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED
            ];

            $flushMode = $options[EntityEventOption::FLUSH_MODE] ?? FlushMode::ENABLED;
            $transactionMode = $options[EntityEventOption::TRANSACTION_MODE] ?? TransactionMode::ENABLED;

            if (TransactionMode::ENABLED === $transactionMode) {
                $this->persistService->beginTransaction();
            }

            $entities = [];
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
     * @param EntityInterface|int|string $entity
     * @param array                      $options
     *
     * @return bool
     *
     * @throws EntityRepositoryException
     */
    public function delete($entity, array $options = []): bool
    {
        if (!is_int($entity) && !is_string($entity) && !$entity instanceof EntityInterface) {
            throw new EntityRepositoryException(
                sprintf(
                    'The \'entity\' argument must be \'int\' or an object of type \'%s\'; \'%s\' provided in \'%s\'',
                    EntityInterface::class,
                    (is_object($entity) ? get_class($entity) : gettype($entity)),
                    __METHOD__
                )
            );
        }

        if (! is_object($entity)) {
            $id = (int) $entity;
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
            $errorMessage = sprintf('Unable to delete entity of type \'%s\': %s', $this->entityName, $e->getMessage());

            $this->logger->error($errorMessage);

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

            $this->logger->error($errorMessage);

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

            $this->logger->error($errorMessage);

            throw new EntityRepositoryException($errorMessage, $e->getCode(), $e);
        }
    }
}
