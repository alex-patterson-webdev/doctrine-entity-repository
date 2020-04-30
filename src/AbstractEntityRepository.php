<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Exception\EntityNotFoundException;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\DoctrineEntityRepository\Query\QueryServiceInterface;
use Arp\Entity\EntityInterface;

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
     * @param string                  $entityName
     * @param QueryServiceInterface   $queryService
     * @param PersistServiceInterface $persistService
     */
    public function __construct(
        string $entityName,
        QueryServiceInterface $queryService,
        PersistServiceInterface $persistService
    ) {
        $this->entityName = $entityName;
        $this->queryService = $queryService;
        $this->persistService = $persistService;
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
            throw new EntityRepositoryException(
                sprintf(
                    'Unable to find entity of type \'%s\' : %s',
                    $this->entityName,
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
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
            throw new EntityRepositoryException(
                sprintf(
                    'Unable to find entity of type \'%s\' : %s',
                    $this->entityName,
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
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
                'limit'    => $limit,
                'offset'   => $offset,
                'order_by' => $orderBy,
            ];

            return $this->queryService->findMany($criteria, $options);
        } catch (\Throwable $e) {
            throw new EntityRepositoryException(
                sprintf(
                    'Unable to return a collection of \'%s\' entities : %s',
                    $this->entityName,
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
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
        } catch (PersistenceException $e) {
            throw new EntityRepositoryException(
                sprintf('Failed to save entity : %s', $e->getMessage())
            );
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
            $saveOptions = array_replace_recursive(
                [
                    EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
                    EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED,
                ],
                $options
            );

            $this->persistService->beginTransaction();

            $entities = [];
            foreach ($collection as $entity) {
                $entities[] = $this->save($entity, $saveOptions);
            }

            $this->persistService->flush($entities);
            $this->persistService->commitTransaction();

            return $entities;
        } catch (PersistenceException $e) {
            throw new EntityRepositoryException(
                sprintf('Failed to save \'%s\' collection : %s', $this->entityName, $e->getMessage()),
                $e->getCode(),
                $e
            );
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
            $id = $entity;
            $entity = $this->find($id);

            if (null === $entity) {
                throw new EntityNotFoundException(
                    sprintf(
                        'Unable to delete entity \'%s::%s\' : The entity could not be found',
                        $this->entityName,
                        $id
                    )
                );
            }
        }

        try {
            return $this->persistService->delete($entity, $options);
        } catch (PersistenceException $e) {
            throw new EntityRepositoryException(
                sprintf('Failed to delete entity : %s', $e->getMessage())
            );
        }
    }
}
