<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class ExceptionListener implements AggregateListenerInterface
{
    /**
     * @param AddListenerAwareInterface $collection
     *
     * @throws EventListenerException
     */
    public function addListeners(AddListenerAwareInterface $collection): void
    {
        $collection->addListenerForEvent(EntityEventName::CREATE_ERROR, [$this, 'onError'], -1000);
        $collection->addListenerForEvent(EntityEventName::UPDATE_ERROR, [$this, 'onError'], -1000);
        $collection->addListenerForEvent(EntityEventName::DELETE_ERROR, [$this, 'onError'], -1000);
        $collection->addListenerForEvent(EntityEventName::SAVE_COLLECTION_ERROR, [$this, 'onError'], -1000);
        $collection->addListenerForEvent(EntityEventName::DELETE_COLLECTION_ERROR, [$this, 'onError'], -1000);
    }

    /**
     * Perform the error handling
     *
     * @param EntityErrorEvent $event
     *
     * @throws PersistenceException
     * @throws \Throwable
     */
    public function onError(EntityErrorEvent $event): void
    {
        $exception = $event->getException();
        if (null === $exception) {
            return;
        }

        $exceptionMessage = sprintf(
            'A persistence error occurred for entity class \'%s\': %s',
            $event->getEntityName(),
            $exception->getMessage()
        );

        if (true === $event->getParam(EntityEventOption::LOG_ERRORS, true)) {
            $event->getLogger()->error($exception->getMessage(), compact('exception'));
        }

        if (!$exception instanceof PersistenceException) {
            $exception = new PersistenceException($exceptionMessage, $exception->getCode(), $exception);
            $event->setException($exception);
        }

        if (true !== $event->getParam(EntityEventOption::THROW_EXCEPTIONS, true)) {
            return;
        }

        throw $exception;
    }
}
