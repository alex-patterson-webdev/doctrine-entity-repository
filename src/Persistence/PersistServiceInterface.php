<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\Entity\EntityInterface;
use Arp\Entity\Repository\Persistence\Exception\PersistServiceException;
use Arp\Entity\Repository\TransactionServiceInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
interface PersistServiceInterface extends TransactionServiceInterface
{
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
     * Persist the entity instance.
     *
     * @param EntityInterface $entity  The entity that should be persisted.
     * @param array           $options The optional persist options.
     *
     * @return EntityInterface
     *
     * @throws PersistServiceException If the persist cannot be performed.
     */
    public function persist(EntityInterface $entity, array $options = []): EntityInterface;

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
     * Flush the database changes.
     *
     * @param EntityInterface[]|EntityInterface|null $entityOrCollection
     * @param array                                  $options
     *
     * @return void
     *
     * @throws PersistServiceException  If the entity or collection cannot be flushed.
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
    public function clear(string $entityName = null): void;
}
