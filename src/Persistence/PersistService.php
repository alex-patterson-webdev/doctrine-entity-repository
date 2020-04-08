<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\PersistEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\PersistErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistServiceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
class PersistService implements PersistServiceInterface
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param string                   $entityName
     * @param EntityManager            $entityManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(
        string $entityName,
        EntityManager $entityManager,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->entityName = $entityName;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * Return the full qualified class name of the entity.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @return EntityInterface
     *
     * @throws PersistServiceException
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface
    {
        if ($entity->hasId()) {
            return $this->update($entity, $options);
        }

        return $this->insert($entity, $options);
    }

    /**
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @return EntityInterface
     *
     * @throws PersistServiceException
     */
    protected function update(EntityInterface $entity, array $options = []): EntityInterface
    {
        try {
            $event = $this->dispatchEvent(
                $this->createEvent(PersistEventName::UPDATE, $entity, $options)
            );

            return $event->getEntity();
        } catch (\Throwable $e) {
            $event = $this->dispatchEvent($this->createErrorEvent(PersistEventName::UPDATE_ERROR, $e));
            /** @var PersistServiceException $exception */
            $exception = $event->getException();
            throw $exception;
        }
    }

    /**
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @return EntityInterface
     *
     * @throws PersistServiceException
     */
    protected function insert(EntityInterface $entity, array $options = []): EntityInterface
    {
        try {
            $event = $this->dispatchEvent(
                $this->createEvent(PersistEventName::INSERT, $entity, $options)
            );

            return $event->getEntity();
        } catch (\Throwable $e) {
            $event = $this->dispatchEvent($this->createErrorEvent(PersistEventName::INSERT_ERROR, $e));
            /** @var PersistServiceException $exception */
            $exception = $event->getException();
            throw $exception;
        }
    }

    /**
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @return bool
     *
     * @throws PersistServiceException
     */
    public function delete(EntityInterface $entity, array $options = []): bool
    {
        try {
            $this->dispatchEvent(
                $this->createEvent(PersistEventName::DELETE, $entity, $options)
            );

            return true;
        } catch (\Throwable $e) {
            $event = $this->dispatchEvent($this->createErrorEvent(PersistEventName::DELETE_ERROR, $e));
            /** @var PersistServiceException $exception */
            $exception = $event->getException();
            throw $exception;
        }
    }

    /**
     * Schedule the entity for insertion.
     *
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @throws PersistServiceException
     */
    public function persist(EntityInterface $entity, array $options = []): void
    {
        try {
            $this->entityManager->persist($entity);
        } catch (\Throwable $e) {
            throw new PersistServiceException(
                sprintf('The persist operation failed for entity \'%s\' : %s', $this->entityName, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Perform a flush of the unit of work.
     *
     * @param EntityInterface|EntityInterface[]|null $entityOrCollection
     * @param array                                  $options
     *
     * @throws PersistServiceException
     */
    public function flush($entityOrCollection = null, array $options = []): void
    {
        try {
            $this->entityManager->flush($entityOrCollection);
        } catch (\Throwable $e) {
            throw new PersistServiceException(
                sprintf('The flush operation failed for entity \'%s\' : %s', $this->entityName, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Release managed entities from the identity map.
     *
     * @param string|null $entityName
     *
     * @return void
     *
     * @throws PersistServiceException
     */
    public function clear(?string $entityName): void
    {
        try {
            $this->entityManager->clear($entityName);
        } catch (\Throwable $e) {
            throw new PersistServiceException(
                sprintf('The flush operation failed for entity \'%s\' : %s', $this->entityName, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws PersistServiceException
     */
    public function beginTransaction(): void
    {
        try {
            $this->entityManager->beginTransaction();
        } catch (\Throwable $e) {
            throw new PersistServiceException(
                sprintf('Failed to begin transaction : %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws PersistServiceException
     */
    public function commitTransaction(): void
    {
        try {
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            throw new PersistServiceException(
                sprintf('Failed to commit transaction : %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws PersistServiceException
     */
    public function rollbackTransaction(): void
    {
        try {
            $this->entityManager->rollback();
        } catch (\Throwable $e) {
            throw new PersistServiceException(
                sprintf('Failed to rollback transaction : %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Perform the event dispatch.
     *
     * @param object $event The event that should be dispatched.
     *
     * @return object
     */
    protected function dispatchEvent(object $event): object
    {
        return $this->eventDispatcher->dispatch($event);
    }

    /**
     * Create a new PersistEvent.
     *
     * @param string               $eventName
     * @param EntityInterface|null $entity
     * @param array                $params
     *
     * @return PersistEvent
     */
    protected function createEvent(string $eventName, EntityInterface $entity = null, array $params = []): PersistEvent
    {
        return new PersistEvent($this, $eventName, $entity, $params);
    }

    /**
     * Create a new PersistErrorEvent.
     *
     * @param string     $eventName
     * @param \Throwable $exception
     * @param array      $params
     *
     * @return PersistErrorEvent
     */
    protected function createErrorEvent(string $eventName, \Throwable $exception, array $params = []): PersistErrorEvent
    {
        return new PersistErrorEvent($this, $eventName, $exception, $params);
    }
}
