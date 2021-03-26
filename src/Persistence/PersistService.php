<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\AbstractEntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\CollectionEvent;
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
     * @var EventDispatcherInterface
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param string                   $entityName
     * @param EntityManagerInterface  $entityManager
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
     * @param array<string|int, mixed> $options
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
     * @param iterable<EntityInterface> $collection The collection of entities that should be saved
     * @param array<string|int, mixed>  $options    the optional save options
     *
     * @return iterable<EntityInterface>
     *
     * @throws PersistenceException
     */
    public function saveCollection(iterable $collection, array $options = []): iterable
    {
        $event = $this->createCollectionEvent(EntityEventName::SAVE_COLLECTION, $collection, $options);

        try {
            /** @var CollectionEvent $event */
            $event = $this->dispatchEvent($event);
        } catch (\Throwable $e) {
            $this->dispatchEvent($this->createErrorEvent(EntityEventName::SAVE_COLLECTION_ERROR, $e));
        }

        return $event->getCollection();
    }

    /**
     * @param EntityInterface          $entity
     * @param array<string|int, mixed> $options
     *
     * @return EntityInterface
     *
     * @throws PersistenceException
     */
    protected function update(EntityInterface $entity, array $options = []): EntityInterface
    {
        $event = $this->createEvent(EntityEventName::UPDATE, $entity, $options);

        try {
            /** @var EntityEvent $event */
            $event = $this->dispatchEvent($event);
        } catch (\Throwable $e) {
            $this->dispatchEvent($this->createErrorEvent(EntityEventName::UPDATE_ERROR, $e));
        }

        return $event->getEntity() ?? $entity;
    }

    /**
     * @param EntityInterface      $entity
     * @param array<string|int, mixed> $options
     *
     * @return EntityInterface
     *
     * @throws PersistenceException
     */
    protected function insert(EntityInterface $entity, array $options = []): EntityInterface
    {
        $event = $this->createEvent(EntityEventName::CREATE, $entity, $options);

        try {
            /** @var EntityEvent $event */
            $event = $this->dispatchEvent($event);
        } catch (\Throwable $e) {
            $this->dispatchEvent($this->createErrorEvent(EntityEventName::CREATE_ERROR, $e));
        }

        return $event->getEntity() ?? $entity;
    }

    /**
     * @param EntityInterface      $entity
     * @param array<string|int, mixed> $options
     *
     * @return bool
     *
     * @throws PersistenceException
     */
    public function delete(EntityInterface $entity, array $options = []): bool
    {
        try {
            $this->dispatchEvent($this->createEvent(EntityEventName::DELETE, $entity, $options));
            return true;
        } catch (\Throwable $e) {
            $this->dispatchEvent($this->createErrorEvent(EntityEventName::DELETE_ERROR, $e));
            return false;
        }
    }

    /**
     * @param iterable<EntityInterface> $collection
     * @param array<string|int, mixed>  $options
     *
     * @return int
     *
     * @throws PersistenceException
     */
    public function deleteCollection(iterable $collection, array $options = []): int
    {
        $event = $this->createCollectionEvent(EntityEventName::DELETE_COLLECTION, $collection, $options);

        try {
            $event = $this->dispatchEvent($event);
        } catch (\Throwable $e) {
            $this->dispatchEvent($this->createErrorEvent(EntityEventName::DELETE_COLLECTION_ERROR, $e));
        }

        return (int)$event->getParam('deleted', 0);
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
        } catch (\Exception $e) {
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
     * @param AbstractEntityEvent $event The event that should be dispatched.
     *
     * @return AbstractEntityEvent
     * @throws PersistenceException
     */
    protected function dispatchEvent(AbstractEntityEvent $event): AbstractEntityEvent
    {
        $result = $this->eventDispatcher->dispatch($event);

        if (!$result instanceof AbstractEntityEvent) {
            throw new PersistenceException(
                sprintf(
                    'The return \'event\' must be an object of type \'%s\'; \'%s\' returned for entity \'%s\'',
                    AbstractEntityEvent::class,
                    get_class($result),
                    $this->getEntityName()
                )
            );
        }

        return $result;
    }

    /**
     * @param string                   $eventName
     * @param EntityInterface|null     $entity
     * @param array<string|int, mixed> $params
     *
     * @return EntityEvent
     */
    protected function createEvent(string $eventName, EntityInterface $entity = null, array $params = []): EntityEvent
    {
        $event = new EntityEvent(
            $eventName,
            $this,
            $this->entityManager,
            $this->logger,
            $params
        );

        if (null !== $entity) {
            $event->setEntity($entity);
        }

        return $event;
    }

    /**
     * @param string                    $eventName
     * @param iterable<EntityInterface> $collection
     * @param array<string|int, mixed>  $params
     *
     * @return CollectionEvent
     */
    protected function createCollectionEvent(
        string $eventName,
        iterable $collection,
        array $params = []
    ): CollectionEvent {
        $event = new CollectionEvent(
            $eventName,
            $this,
            $this->entityManager,
            $this->logger,
            $params
        );

        $event->setCollection($collection);

        return $event;
    }

    /**
     * @param string               $eventName
     * @param \Throwable           $exception
     * @param array<string|int, mixed> $params
     *
     * @return EntityErrorEvent
     */
    protected function createErrorEvent(string $eventName, \Throwable $exception, array $params = []): EntityErrorEvent
    {
        return new EntityErrorEvent(
            $eventName,
            $this,
            $this->entityManager,
            $this->logger,
            $exception,
            $params
        );
    }
}
