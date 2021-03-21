<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
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
    protected string $entityName;

    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @param string                   $entityName
     * @param EntityManagerInterface   $entityManager
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface          $logger
     */
    public function __construct(
        string $entityName,
        EntityManagerInterface $entityManager,
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
     * @param EntityInterface      $entity
     * @param array<string, mixed> $options
     *
     * @return EntityInterface
     *
     * @throws PersistenceException
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface
    {
        if ($entity->hasId()) {
            return $this->update($entity, $options);
        }

        return $this->insert($entity, $options);
    }

    /**
     * @param EntityInterface      $entity
     * @param array<string, mixed> $options
     *
     * @return EntityInterface
     *
     * @throws PersistenceException
     */
    protected function update(EntityInterface $entity, array $options = []): EntityInterface
    {
        try {
            /** @var EntityEvent $event */
            $event = $this->dispatchEvent($this->createEvent(EntityEventName::UPDATE, $entity, $options));
            /** @var EntityInterface $entity */
            $entity = $event->getEntity();
            return $entity;
        } catch (\Throwable $e) {
            /** @var EntityErrorEvent $event */
            $event = $this->dispatchEvent($this->createErrorEvent(EntityEventName::UPDATE_ERROR, $e));

            throw $this->createEventException($event->getException());
        }
    }

    /**
     * @param EntityInterface      $entity
     * @param array<string, mixed> $options
     *
     * @return EntityInterface
     *
     * @throws PersistenceException
     */
    protected function insert(EntityInterface $entity, array $options = []): EntityInterface
    {
        try {
            /** @var EntityEvent $event */
            $event = $this->dispatchEvent($this->createEvent(EntityEventName::CREATE, $entity, $options));

            /** @var EntityInterface $entity */
            $entity = $event->getEntity();
            return $entity;
        } catch (\Throwable $e) {
            /** @var EntityErrorEvent $event */
            $event = $this->dispatchEvent($this->createErrorEvent(EntityEventName::CREATE_ERROR, $e));

            throw $this->createEventException($event->getException());
        }
    }

    /**
     * @param EntityInterface      $entity
     * @param array<string, mixed> $options
     *
     * @return bool
     *
     * @throws PersistenceException
     */
    public function delete(EntityInterface $entity, array $options = []): bool
    {
        try {
            $event = $this->createEvent(EntityEventName::DELETE, $entity, $options);
            $this->dispatchEvent($event);
            return true;
        } catch (\Throwable $e) {
            /** @var EntityErrorEvent $errorEvent */
            $errorEvent = $this->dispatchEvent(
                $this->createErrorEvent(EntityEventName::DELETE_ERROR, $e)
            );

            throw $this->createEventException($errorEvent->getException());
        }
    }

    /**
     * Schedule the entity for insertion.
     *
     * @param EntityInterface $entity
     *
     * @throws PersistenceException
     */
    public function persist(EntityInterface $entity): void
    {
        if (!$entity instanceof $this->entityName) {
            $errorMessage = sprintf(
                'The \'entity\' argument must be an object of type \'%s\'; \'%s\' provided in \'%s\'',
                $this->entityName,
                get_class($entity),
                __METHOD__
            );

            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        try {
            $this->entityManager->persist($entity);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'The persist operation failed for entity \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e]);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Perform a flush of the unit of work.
     *
     * @throws PersistenceException
     */
    public function flush(): void
    {
        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'The flush operation failed for entity \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e, 'entity_name' => $this->entityName]);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * Release managed entities from the identity map.
     *
     * @return void
     *
     * @throws PersistenceException
     */
    public function clear(): void
    {
        try {
            $this->entityManager->clear();
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'The clear operation failed for entity \'%s\': %s',
                $this->entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage, ['exception' => $e, 'entity_name' => $this->entityName]);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }

    /**
     * @param EntityInterface $entity
     *
     * @throws PersistenceException
     */
    public function refresh(EntityInterface $entity): void
    {
        $entityName = $this->entityName;

        if (!$entity instanceof $entityName) {
            throw new PersistenceException(
                sprintf(
                    'The \'entity\' argument must be an object of type \'%s\'; \'%s\' provided in \'%s\'',
                    $entityName,
                    get_class($entity),
                    __METHOD__
                )
            );
        }

        try {
            $this->entityManager->refresh($entity);
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf(
                    'The refresh operation failed for entity \'%s\' : %s',
                    $entityName,
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws PersistenceException
     */
    public function beginTransaction(): void
    {
        try {
            $this->entityManager->beginTransaction();
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf('Failed to start transaction : %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws PersistenceException
     */
    public function commitTransaction(): void
    {
        try {
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            throw new PersistenceException(
                sprintf('Failed to commit transaction : %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws PersistenceException
     */
    public function rollbackTransaction(): void
    {
        try {
            $this->entityManager->rollback();
        } catch (\Throwable $e) {
            throw new PersistenceException(
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
     * @param array<string, mixed> $params
     *
     * @return EntityEvent
     */
    protected function createEvent(string $eventName, EntityInterface $entity = null, array $params = []): EntityEvent
    {
        $event = new EntityEvent(
            $eventName,
            $this->entityName,
            $this->entityManager,
            $params
        );

        if (null !== $entity) {
            $event->setEntity($entity);
        }

        return $event;
    }

    /**
     * Create a new PersistErrorEvent.
     *
     * @param string               $eventName
     * @param \Throwable           $exception
     * @param array<string, mixed> $params
     *
     * @return EntityErrorEvent
     */
    protected function createErrorEvent(string $eventName, \Throwable $exception, array $params = []): EntityErrorEvent
    {
        return new EntityErrorEvent(
            $eventName,
            $this->entityName,
            $this->entityManager,
            $exception,
            $params
        );
    }

    /**
     * @param \Throwable $exception
     *
     * @return PersistenceException
     */
    private function createEventException(\Throwable $exception): PersistenceException
    {
        $errorMessage = sprintf(
            'The persistence operation for entity \'%s\' failed: %s',
            $this->entityName,
            $exception->getMessage()
        );

        /** @var PersistenceException $exception */
        if (!$exception instanceof PersistenceException) {
            $exception = new PersistenceException($errorMessage, $exception->getCode(), $exception);
        }

        $this->logger->error($errorMessage, compact('exception'));

        return $exception;
    }
}
