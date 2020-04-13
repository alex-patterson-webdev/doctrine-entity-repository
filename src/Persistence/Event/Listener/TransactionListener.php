<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class TransactionListener implements AggregateListenerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param AddListenerAwareInterface $collection
     *
     * @throws EventListenerException
     */
    public function addListeners(AddListenerAwareInterface $collection): void
    {
        $collection->addListenerForEvent(EntityEventName::CREATE, [$this, 'beginTransaction'], 1000);
        $collection->addListenerForEvent(EntityEventName::CREATE, [$this, 'commitTransaction'], 1);
        $collection->addListenerForEvent(EntityEventName::CREATE_ERROR, [$this, 'rollbackTransaction'], 1000);

        $collection->addListenerForEvent(EntityEventName::UPDATE, [$this, 'beginTransaction'], 1000);
        $collection->addListenerForEvent(EntityEventName::UPDATE, [$this, 'commitTransaction'], 1);
        $collection->addListenerForEvent(EntityEventName::UPDATE_ERROR, [$this, 'rollbackTransaction'], 1000);

        $collection->addListenerForEvent(EntityEventName::DELETE, [$this, 'beginTransaction'], 1000);
        $collection->addListenerForEvent(EntityEventName::DELETE, [$this, 'commitTransaction'], 1);
        $collection->addListenerForEvent(EntityEventName::DELETE_ERROR, [$this, 'rollbackTransaction'], 1000);
    }

    /**
     * @param EntityEvent $event
     */
    public function beginTransaction(EntityEvent $event): void
    {
        if (!$this->isEnabled($event, __FUNCTION__)) {
            return;
        }
        $entity = $event->getEntity();

        $this->logger->info(
            sprintf(
                'Starting a new \'%s\' transaction for entity \'%s::%s\'',
                $event->getEventName(),
                $event->getEntityName(),
                (isset($entity) ? $entity->getId() : '0')
            )
        );

        $event->getEntityManager()->beginTransaction();
    }

    /**
     * @param EntityEvent $event
     */
    public function commitTransaction(EntityEvent $event): void
    {
        if (!$this->isEnabled($event, __FUNCTION__)) {
            return;
        }
        $entity = $event->getEntity();

        $this->logger->info(
            sprintf(
                'Committing\'%s\' transaction for entity \'%s::%s\'',
                $event->getEventName(),
                $event->getEntityName(),
                (isset($entity) ? $entity->getId() : '0')
            )
        );

        $event->getEntityManager()->commit();
    }

    /**
     * @param EntityEvent $event
     */
    public function rollbackTransaction(EntityEvent $event): void
    {
        if (!$this->isEnabled($event, __FUNCTION__)) {
            return;
        }
        $entity = $event->getEntity();

        $this->logger->info(
            sprintf(
                'Rolling back  \'%s\' transaction for entity \'%s::%s\'',
                $event->getEventName(),
                $event->getEntityName(),
                (isset($entity) ? $entity->getId() : '0')
            )
        );

        $event->getEntityManager()->rollback();
    }

    /**
     * @param EntityEvent $event
     * @param string      $methodName
     *
     * @return bool
     */
    private function isEnabled(EntityEvent $event, string $methodName): bool
    {
        $transactionMode = $event->getParameters()->getParam(EntityEventOption::TRANSACTION_MODE);

        if (TransactionMode::ENABLED !== $transactionMode) {
            $entityName = $event->getEntityName();
            $eventName = $event->getEventName();
            $entity = $event->getEntity();

            $this->logger->info(
                sprintf(
                    'Skipping \'%s::%s\' operation for entity \'%s::%s\' with mode \'%s\'',
                    $eventName,
                    $methodName,
                    $entityName,
                    (isset($entity) ? $entity->getId() : '0'),
                    $transactionMode
                ),
                compact('eventName', 'entityName', 'transactionMode')
            );

            return false;
        }

        return true;
    }
}
