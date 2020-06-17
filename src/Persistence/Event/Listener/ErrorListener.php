<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class ErrorListener implements AggregateListenerInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

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
        $collection->addListenerForEvent(EntityEventName::CREATE_ERROR, [$this, 'onError'], -1000);
        $collection->addListenerForEvent(EntityEventName::UPDATE_ERROR, [$this, 'onError'], -1000);
        $collection->addListenerForEvent(EntityEventName::DELETE_ERROR, [$this, 'onError'], -1000);
    }

    /**
     * Perform the error handling.
     *
     * @param EntityErrorEvent $event
     */
    public function onError(EntityErrorEvent $event): void
    {
        $exception = $event->getException();

        if (!$exception instanceof PersistenceException) {
            $exceptionMessage = sprintf(
                'An error occurred while performing the \'%s\' event for entity class \'%s\': %s',
                $event->getEventName(),
                $event->getEntityName(),
                $exception->getMessage()
            );

            $exception = new PersistenceException($exceptionMessage, $exception->getCode(), $exception);
            $event->setException($exception);
        }

        $this->logger->error($exception->getMessage(), compact('exception'));
    }
}
