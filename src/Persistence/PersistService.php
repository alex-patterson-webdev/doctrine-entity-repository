<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManager;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
class PersistService implements PersistServiceInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(EntityInterface $entity, array $options = []): EntityInterface
    {
    }

    public function persist(EntityInterface $entity, array $options = []): EntityInterface
    {
    }

    public function delete(EntityInterface $entity, array $options = []): bool
    {
    }

    public function flush($entityOrCollection = null, array $options = []): void
    {
    }

    public function clear(string $entityName = null): void
    {
    }

    public function beginTransaction(): void
    {
    }

    public function commitTransaction(): void
    {
    }

    public function rollbackTransaction(): void
    {
    }
}
