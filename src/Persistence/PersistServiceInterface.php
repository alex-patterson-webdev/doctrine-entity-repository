<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
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
     * @param EntityInterface      $entity  The entity instance that should be saved.
     * @param array<string, mixed> $options The optional save options.
     *
     * @return EntityInterface
     *
     * @throws PersistenceException  If the entity cannot be saved.
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface;

    /**
     * Schedule the entity for insertion.
     *
     * @param EntityInterface $entity
     *
     * @throws PersistenceException
     */
    public function persist(EntityInterface $entity): void;

    /**
     * Delete an entity instance.
     *
     * @param EntityInterface      $entity  The entity that should be deleted.
     * @param array<string, mixed> $options The optional deletion options.
     *
     * @return boolean
     *
     * @throws PersistenceException  If the collection cannot be deleted.
     */
    public function delete(EntityInterface $entity, array $options = []): bool;

    /**
     * Perform a flush of the unit of work.
     *
     * @throws PersistenceException
     */
    public function flush(): void;

    /**
     * Release managed entities from the identity map.
     *
     * @return void
     *
     * @throws PersistenceException
     */
    public function clear(): void;

    /**
     * @param EntityInterface $entity
     *
     * @throws PersistenceException
     */
    public function refresh(EntityInterface $entity): void;
}
