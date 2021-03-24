<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\InvalidArgumentException;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class EntityValidationListener implements AggregateListenerInterface
{
    /**
     * @param AddListenerAwareInterface $collection
     *
     * @throws EventListenerException
     */
    public function addListeners(AddListenerAwareInterface $collection): void
    {
        $collection->addListenerForEvent(EntityEventName::CREATE, [$this, 'validateEntity'], 1000);
        $collection->addListenerForEvent(EntityEventName::UPDATE, [$this, 'validateEntity'], 1000);
        $collection->addListenerForEvent(EntityEventName::DELETE, [$this, 'validateEntity'], 1000);
    }

    /**
     * Ensure that the entity is valid.
     *
     * @param EntityEvent $event
     *
     * @throws InvalidArgumentException
     */
    public function validateEntity(EntityEvent $event): void
    {
        $logger = $event->getLogger();
        $eventName = $event->getEventName();
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (null === $entity) {
            $errorMessage = sprintf(
                'The required \'%s\' entity instance was not set for event \'%s\'',
                $entityName,
                $eventName
            );

            $logger->error($errorMessage, ['entity_name' => $entityName, 'event_name' => $eventName]);

            throw new InvalidArgumentException($errorMessage);
        }

        if (!$entity instanceof $entityName) {
            $errorMessage = sprintf(
                'The entity class of type \'%s\' does not match the expected \'%s\' for event \'%s\'',
                get_class($entity),
                $entityName,
                $eventName
            );

            $logger->error($errorMessage, ['entity_name' => $entityName, 'event_name' => $eventName]);

            throw new InvalidArgumentException($errorMessage);
        }

        $logger->debug(
            sprintf(
                'Successfully completed validation of \'%s\' for event \'%s\'',
                $entityName,
                $eventName
            )
        );
    }
}
