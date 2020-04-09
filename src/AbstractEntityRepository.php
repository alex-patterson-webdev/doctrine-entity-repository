<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistServiceException;
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
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Return a single entity instance matching the provided $criteria.
     *
     * @param array $criteria  The entity filter criteria.
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
        } catch (PersistServiceException $e) {
            throw new EntityRepositoryException(
                sprintf('Failed to save entity : %s', $e->getMessage())
            );
        }
    }
}
