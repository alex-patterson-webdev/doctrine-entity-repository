<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\Entity\EntityInterface;
use Doctrine\Common\Persistence\ObjectRepository;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository
 */
interface EntityRepositoryInterface extends ObjectRepository
{
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
    public function save(EntityInterface $entity, array $options = []): EntityInterface;

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
    public function saveCollection(iterable $collection, array $options = []): iterable;

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
    public function delete($entity, array $options = []): bool;

    /**
     * @throws EntityRepositoryException
     */
    public function clear(): void;

    /**
     * @param EntityInterface $entity
     *
     * @throws EntityRepositoryException
     */
    public function refresh(EntityInterface $entity): void;
}
