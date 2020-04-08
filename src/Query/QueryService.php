<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Query;

use Arp\Entity\EntityInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Query
 */
class QueryService implements QueryServiceInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string          $entityName
     * @param EntityManager   $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(string $entityName, EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->entityName = $entityName;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Return a new query builder instance.
     *
     * @param string|null $alias The optional query builder alias.
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder(string $alias = null): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        if (null !== $alias) {
            $queryBuilder->select($alias)->from($this->entityName, $alias);
        }

        return $queryBuilder;
    }

    /**
     * @param AbstractQuery|QueryBuilder $queryOrBuilder
     * @param array                      $options
     *
     * @return EntityInterface|null
     *
     * @throws QueryServiceException
     */
    protected function getSingleResultOrNull($queryOrBuilder, array $options = [])
    {
        return $this->execute($queryOrBuilder, $options);
    }

    /**
     * Construct and execute the query.
     *
     * @param AbstractQuery|QueryBuilder $queryOrBuilder
     * @param array                      $options
     *
     * @return mixed
     *
     * @throws QueryServiceException
     */
    protected function execute($queryOrBuilder, array $options = [])
    {
        if ($queryOrBuilder instanceof QueryBuilder) {
            $this->prepareQueryBuilder($queryOrBuilder);

            $queryOrBuilder = $queryOrBuilder->getQuery();
        }

        if (!$queryOrBuilder instanceof AbstractQuery) {
            throw new QueryServiceException(
                sprintf(
                    'Query provided must be of type \'%s\'; \'%s\' provided in \'%s\'.',
                    AbstractQuery::class,
                    (is_object($queryOrBuilder) ? get_class($queryOrBuilder) : gettype($queryOrBuilder)),
                    __METHOD__
                )
            );
        }

        try {
            return $this->prepareQuery($queryOrBuilder, $options)->execute();
        } catch (\Throwable $e) {
            throw new QueryServiceException(
                sprintf('Failed to execute query : %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Find a single entity matching the provided identity.
     *
     * @param mixed $id      The identity of the entity to match.
     * @param array $options The optional query options.
     *
     * @return EntityInterface|null
     *
     * @throws QueryServiceException
     */
    public function findOneById($id, array $options = []): ?EntityInterface
    {
        return $this->findOne(compact('id'), $options);
    }

    /**
     * Find a single entity matching the provided criteria.
     *
     * @param array $criteria The search criteria that should be matched on.
     * @param array $options  The optional query options.
     *
     * @return EntityInterface|null
     *
     * @throws QueryServiceException
     */
    public function findOne(array $criteria, array $options = []): ?EntityInterface
    {
        $orderBy = isset($options['order_by']) ? $options['order_by'] : null;

        try {
            $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($this->entityName);

            $entity = $persister->load($criteria, null, null, [], null, 1, $orderBy);

            return ($entity instanceof EntityInterface) ? $entity : null;
        } catch (\Throwable $e) {
            throw new QueryServiceException(
                sprintf('Failed to execute \'findOne\' query: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Find a collection of entities that match the provided criteria.
     *
     * @param array $criteria The search criteria that should be matched on.
     * @param array $options  The optional query options.
     *
     * @return EntityInterface[]
     *
     * @throws QueryServiceException
     */
    public function findMany(array $criteria, array $options = [])
    {
        $orderBy = $options['order_by'] ?? null;
        $limit = $options['limit'] ?? null;
        $offset = $options['offset'] ?? null;

        try {
            $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($this->entityName);

            return $persister->loadAll($criteria, $orderBy, $limit, $offset);
        } catch (\Throwable $e) {
            throw new QueryServiceException(
                sprintf('Failed to execute \'findMany\' : %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Set the query builder options.
     *
     * @param QueryBuilder $queryBuilder The query builder to update.
     * @param array        $options      The query builder options to set.
     *
     * @return QueryBuilder
     */
    protected function prepareQueryBuilder(QueryBuilder $queryBuilder, array $options = []): QueryBuilder
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'first_result' :
                    $queryBuilder->setFirstResult($value);
                break;

                case 'max_results' :
                    $queryBuilder->setMaxResults($value);
                break;
            }
        }
        return $queryBuilder;
    }

    /**
     * Prepare the provided query by setting the $options.
     *
     * @param AbstractQuery $query
     * @param array         $options
     *
     * @return AbstractQuery
     */
    protected function prepareQuery(AbstractQuery $query, array $options = [])
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'params' :
                    $query->setParameters($value);
                break;

                case 'hydration_mode' :
                    $query->setHydrationMode($value);
                break;

                case 'hydration_cache_profile' :
                    $query->setHydrationCacheProfile($value);
                break;

                case 'result_set_mapping' :
                    $query->setResultSetMapping($value);
                break;

                case 'hints' :
                    if (is_array($value)) {
                        foreach ($value as $hint => $hintValue) {
                            $query->setHint($hint, $hintValue);
                        }
                    }
                break;
            }

            if ($query instanceof Query) {
                switch ($name) {
                    case 'dql' :
                        $query->setDQL($value);
                    break;
                }
            }
        }

        return $query;
    }
}
