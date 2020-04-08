<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Query;

use Arp\DoctrineEntityRepository\Query\Exception\QueryServiceException;
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
    public function createQueryBuilder(string $alias = null): QueryBuilder
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
     * @return EntityInterface|null|array
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
            $query = $this->prepareQuery($queryOrBuilder, $options);

            if (isset($options['log_sql']) && true === $options['log_sql']) {
                $this->logger->debug(
                    sprintf('Executing SQL : %s', $query->getSQL()),
                    $query->getParameters()->toArray()
                );
            }
            return $query->execute();
        } catch (\Throwable $e) {
            $message = sprintf('Failed to execute query : %s', $e->getMessage());

            $this->logger->error($message, ['exception' => $e]);

            throw new QueryServiceException($message, $e->getCode(), $e);
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
        if (isset($options['entity']) && $options['entity'] instanceof $this->entityName) {
            $entity = $options['entity'];
        }

        try {
            $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($this->entityName);

            $entity = $persister->load(
                $criteria,
                $entity ?? null,
                $options['association'] ?? null,
                $options['hints'] ?? [],
                $options['lock_mode'] ?? null,
                1,
                $options['order_by'] ?? null
            );

            return ($entity instanceof EntityInterface) ? $entity : null;
        } catch (\Throwable $e) {
            $message = sprintf('Failed to execute \'findOne\' query: %s', $e->getMessage());

            $this->logger->error($message, ['exception' => $e, 'criteria' => $criteria, 'options' => $options]);

            throw new QueryServiceException($message, $e->getCode(), $e);
        }
    }

    /**
     * Find a collection of entities that match the provided criteria.
     *
     * @param array $criteria The search criteria that should be matched on.
     * @param array $options  The optional query options.
     *
     * @return EntityInterface[]|\Traversable
     *
     * @throws QueryServiceException
     */
    public function findMany(array $criteria, array $options = [])
    {
        try {
            $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($this->entityName);

            return $persister->loadAll(
                $criteria,
                $options['order_by'] ?? null,
                $options['limit'] ?? null,
                $options['offset'] ?? null
            );
        } catch (\Throwable $e) {
            $message = sprintf('Failed to execute \'findMany\' query: %s', $e->getMessage());

            $this->logger->error($message, ['exception' => $e, 'criteria' => $criteria, 'options' => $options]);

            throw new QueryServiceException($message, $e->getCode(), $e);
        }
    }

    /**
     * Return the result set count.
     *
     * @param array $criteria
     *
     * @return mixed
     */
    public function count(array $criteria)
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();

        return $unitOfWork->getEntityPersister($this->entityName)->count($criteria);
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
        if (array_key_exists('first_result', $options)) {
            $queryBuilder->setFirstResult($options['first_result']);
        }

        if (array_key_exists('max_results', $options)) {
            $queryBuilder->setMaxResults($options['max_results']);
        }

        return $queryBuilder;
    }

    /**
     * Prepare the provided query by setting the $options.
     *
     * @todo Reduce cyclomatic complexity
     *
     * @param AbstractQuery $query
     * @param array         $options
     *
     * @return AbstractQuery
     */
    protected function prepareQuery(AbstractQuery $query, array $options = []): AbstractQuery
    {
        if (array_key_exists('params', $options)) {
            $query->setParameters($options['params']);
        }

        if (array_key_exists('hydration_mode', $options)) {
            $query->setHydrationMode($options['hydration_mode']);
        }

        if (array_key_exists('hydration_cache_profile', $options)) {
            $query->setHydrationCacheProfile($options['hydration_cache_profile']);
        }

        if (array_key_exists('result_set_mapping', $options)) {
            $query->setResultSetMapping($options['result_set_mapping']);
        }

        if (isset($options['hints']) && is_array($options['hints'])) {
            foreach ($options['hints'] as $hint => $hintValue) {
                $query->setHint($hint, $hintValue);
            }
        }

        if (! empty($options['dql']) && $query instanceof Query) {
            $query->setDQL($options['dql']);
        }

        return $query;
    }
}
