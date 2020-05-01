<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Query;

use Arp\DoctrineEntityRepository\Query\Exception\QueryServiceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Query
 */
interface QueryServiceInterface
{
    /**
     * Find a single entity matching the provided identity.
     *
     * @param int|string $id      The identity of the entity to match.
     * @param array      $options The optional query options.
     *
     * @return EntityInterface|null
     *
     * @throws QueryServiceException
     */
    public function findOneById($id, array $options = []): ?EntityInterface;

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
    public function findOne(array $criteria, array $options = []): ?EntityInterface;

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
    public function findMany(array $criteria, array $options = []);

    /**
     * @param AbstractQuery|QueryBuilder $queryOrBuilder
     * @param array                      $options
     *
     * @return EntityInterface|null|array
     *
     * @throws QueryServiceException
     */
    public function getSingleResultOrNull($queryOrBuilder, array $options = []);

    /**
     * Return a new query builder instance.
     *
     * @param string|null $alias The optional query builder alias.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(string $alias = null): QueryBuilder;

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
    public function execute($queryOrBuilder, array $options = []);

    /**
     * Return the result set count.
     *
     * @param array $criteria
     *
     * @return mixed
     */
    public function count(array $criteria);
}
