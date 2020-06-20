<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DateDeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DateDeletedAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
class DateDeletedListener extends AbstractDateTimeListener
{
    /**
     * Check if the created entity implements DateCreatedAwareInterface and set a new date time instance on the
     * dateCreated property.
     *
     * @param EntityEvent $event
     *
     * @throws PersistenceException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (null === $entity || !$entity instanceof DateDeletedAwareInterface) {
            $this->logger->debug(
                sprintf(
                    'Ignoring update date time for entity \'%s\': The entity does not implement \'%s\'',
                    $entityName,
                    DateDeletedAwareInterface::class
                )
            );
            return;
        }

        $mode = $event->getParameters()->getParam(EntityEventOption::DATE_DELETED_MODE, DateDeleteMode::ENABLED);
        $entityId = $entity->getId();

        if (DateDeleteMode::ENABLED !== $mode) {
            $this->logger->info(
                sprintf(
                    'The date time update of field \'dateDeleted\' '
                    . 'has been disabled for entity \'%s::%s\' using configuration option \'%s\'',
                    $entityName,
                    $entityId,
                    EntityEventOption::DATE_DELETED_MODE
                )
            );
            return;
        }

        $dateDeleted = $this->createDateTime($entityName, $entityId);
        $entity->setDateDeleted($dateDeleted);

        $this->logger->info(
            sprintf(
                'The \'dateDeleted\' property for entity \'%s::%s\' has been updated with new date time \'%s\'',
                $entityName,
                $entityId,
                $dateDeleted->format(\DateTime::ATOM)
            )
        );
    }
}
