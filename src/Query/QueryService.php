<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Query;

use Arp\DoctrineEntityRepository\Constant\QueryServiceOption;
use Arp\DoctrineEntityRepository\Query\Exception\QueryServiceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
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
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string                 $entityName
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(string $entityName, EntityManagerInterface $entityManager, LoggerInterface $logger)
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
    public function getSingleResultOrNull($queryOrBuilder, array $options = [])
    {
        $result = $this->execute($queryOrBuilder, $options);

        if (empty($result)) {
            return null;
        }
        if (!is_array($result)) {
            return $result;
        }
        if (count($result) > 1) {
            return null;
        }

        return array_shift($result);
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
    public function execute($queryOrBuilder, array $options = [])
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
        try {
            $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($this->entityName);

            $entity = $persister->load(
                $criteria,
                $options[QueryServiceOption::ENTITY] ?? null,
                $options[QueryServiceOption::ASSOCIATION] ?? null,
                $options[QueryServiceOption::HINTS] ?? [],
                $options[QueryServiceOption::LOCK_MODE] ?? null,
                1,
                $options[QueryServiceOption::ORDER_BY] ?? null
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
     * @return EntityInterface[]|iterable
     *
     * @throws QueryServiceException
     */
    public function findMany(array $criteria, array $options = []): iterable
    {
        try {
            $persister = $this->entityManager->getUnitOfWork()->getEntityPersister($this->entityName);

            return $persister->loadAll(
                $criteria,
                $options[QueryServiceOption::ORDER_BY] ?? null,
                $options[QueryServiceOption::LIMIT] ?? null,
                $options[QueryServiceOption::OFFSET] ?? null
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
        if (array_key_exists(QueryServiceOption::FIRST_RESULT, $options)) {
            $queryBuilder->setFirstResult($options[QueryServiceOption::FIRST_RESULT]);
        }

        if (array_key_exists(QueryServiceOption::MAX_RESULTS, $options)) {
            $queryBuilder->setMaxResults($options[QueryServiceOption::MAX_RESULTS]);
        }

        if (array_key_exists(QueryServiceOption::ORDER_BY, $options)) {
            foreach ($options[QueryServiceOption::ORDER_BY] as $fieldName => $orderDirection) {
                $queryBuilder->addOrderBy(
                    $fieldName,
                    ('DESC' === strtoupper($orderDirection) ? 'DESC' : 'ASC')
                );
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @todo Reduce cyclomatic complexity
     *
     */
    protected function prepareQuery(AbstractQuery $query, array $options = []): AbstractQuery
    {
        if (array_key_exists('params', $options)) {
            $query->setParameters($options['params']);
        }

        if (array_key_exists(QueryServiceOption::HYDRATION_MODE, $options)) {
            $query->setHydrationMode($options[QueryServiceOption::HYDRATION_MODE]);
        }

        if (array_key_exists('result_set_mapping', $options)) {
            $query->setResultSetMapping($options['result_set_mapping']);
        }

        if (isset($options[QueryServiceOption::HINTS]) && is_array($options[QueryServiceOption::HINTS])) {
            foreach ($options[QueryServiceOption::HINTS] as $hint => $hintValue) {
                $query->setHint($hint, $hintValue);
            }
        }

        if (!empty($options[QueryServiceOption::DQL]) && $query instanceof Query) {
            $query->setDQL($options[QueryServiceOption::DQL]);
        }

        return $query;
    }
}
