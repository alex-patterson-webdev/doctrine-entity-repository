<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Persistence\Exception\PersistServiceException;
use Arp\Entity\EntityInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
interface PersistServiceInterface extends TransactionServiceInterface
{
    /**
     * Return the full qualified class name of the entity.
     *
     * @return string
     */
    public function getEntityName(): string;

    /**
     * Create or update a entity instance.
     *
     * @param EntityInterface $entity  The entity instance that should be saved.
     * @param array           $options The optional save options.
     *
     * @return EntityInterface
     *
     * @throws PersistServiceException  If the entity cannot be saved.
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface;

    /**
     * Schedule the entity for insertion.
     *
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @throws PersistServiceException
     */
    public function persist(EntityInterface $entity, array $options = []): void;

    /**
     * Delete an entity instance.
     *
     * @param EntityInterface $entity  The entity that should be deleted.
     * @param array           $options The optional deletion options.
     *
     * @return boolean
     *
     * @throws PersistServiceException  If the collection cannot be deleted.
     */
    public function delete(EntityInterface $entity, array $options = []): bool;

    /**
     * Perform a flush of the unit of work.
     *
     * @param EntityInterface|EntityInterface[]|null $entityOrCollection
     * @param array                                  $options
     *
     * @throws PersistServiceException
     */
    public function flush($entityOrCollection = null, array $options = []): void;

    /**
     * Release managed entities from the identity map.
     *
     * @param string|null $entityName
     *
     * @return void
     *
     * @throws PersistServiceException
     */
    public function clear(?string $entityName): void;
}
