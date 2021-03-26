<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Persistence\Event\AbstractEntityEvent;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class TransactionListener implements AggregateListenerInterface
{
    /**
     * @param AddListenerAwareInterface $collection
     *
     * @throws EventListenerException
     */
    public function addListeners(AddListenerAwareInterface $collection): void
    {
        $collection->addListenerForEvent(EntityEventName::CREATE, [$this, 'beginTransaction'], 900);
        $collection->addListenerForEvent(EntityEventName::UPDATE, [$this, 'beginTransaction'], 900);
        $collection->addListenerForEvent(EntityEventName::DELETE, [$this, 'beginTransaction'], 900);
        $collection->addListenerForEvent(EntityEventName::SAVE_COLLECTION, [$this, 'beginTransaction'], 900);
        $collection->addListenerForEvent(EntityEventName::DELETE_COLLECTION, [$this, 'beginTransaction'], 900);

        $collection->addListenerForEvent(EntityEventName::CREATE, [$this, 'commitTransaction']);
        $collection->addListenerForEvent(EntityEventName::UPDATE, [$this, 'commitTransaction']);
        $collection->addListenerForEvent(EntityEventName::DELETE, [$this, 'commitTransaction']);
        $collection->addListenerForEvent(EntityEventName::SAVE_COLLECTION, [$this, 'commitTransaction']);
        $collection->addListenerForEvent(EntityEventName::DELETE_COLLECTION, [$this, 'commitTransaction']);

        $collection->addListenerForEvent(EntityEventName::CREATE_ERROR, [$this, 'rollbackTransaction'], 1000);
        $collection->addListenerForEvent(EntityEventName::UPDATE_ERROR, [$this, 'rollbackTransaction'], 1000);
        $collection->addListenerForEvent(EntityEventName::DELETE_ERROR, [$this, 'rollbackTransaction'], 1000);
        $collection->addListenerForEvent(
            EntityEventName::SAVE_COLLECTION_ERROR,
            [$this, 'rollbackTransaction'],
            1000
        );
        $collection->addListenerForEvent(
            EntityEventName::DELETE_COLLECTION_ERROR,
            [$this, 'rollbackTransaction'],
            1000
        );
    }

    /**
     * @param AbstractEntityEvent $event
     */
    public function beginTransaction(AbstractEntityEvent $event): void
    {
        if (!$this->isEnabled($event)) {
            return;
        }

        $event->getLogger()->debug(
            sprintf(
                'Starting a new \'%s\' transaction for entity \'%s\'',
                $event->getEventName(),
                $event->getEntityName()
            )
        );

        $event->getPersistService()->beginTransaction();
    }

    /**
     * @param AbstractEntityEvent $event
     */
    public function commitTransaction(AbstractEntityEvent $event): void
    {
        if (!$this->isEnabled($event)) {
            return;
        }

        $event->getLogger()->debug(
            sprintf(
                'Committing \'%s\' transaction for entity \'%s\'',
                $event->getEventName(),
                $event->getEntityName()
            )
        );

        $event->getPersistService()->commitTransaction();
    }

    /**
     * @param AbstractEntityEvent $event
     */
    public function rollbackTransaction(AbstractEntityEvent $event): void
    {
        if (!$this->isEnabled($event)) {
            return;
        }

        $event->getLogger()->debug(
            sprintf(
                'Rolling back \'%s\' transaction for entity class \'%s\'',
                $event->getEventName(),
                $event->getEntityName()
            )
        );

        $event->getPersistService()->rollbackTransaction();
    }

    /**
     * @param AbstractEntityEvent $event
     *
     * @return bool
     */
    private function isEnabled(AbstractEntityEvent $event): bool
    {
        $transactionMode = $event->getParam(EntityEventOption::TRANSACTION_MODE, TransactionMode::ENABLED);
        $entityName = $event->getEntityName();

        if (TransactionMode::ENABLED === $transactionMode) {
            return true;
        }

        $event->getLogger()->debug(
            sprintf(
                'Transactions are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                $entityName,
                TransactionMode::DISABLED,
                EntityEventOption::TRANSACTION_MODE
            ),
            [
                'entity_name' => $entityName,
                EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED
            ]
        );

        return false;
    }
}
