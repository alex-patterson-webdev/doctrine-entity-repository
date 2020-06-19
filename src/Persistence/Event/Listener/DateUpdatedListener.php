<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Constant\DateUpdateMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DateUpdatedAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
class DateUpdatedListener extends AbstractDateTimeListener
{
    /**
     * Check if the updated entity implements DateUpdatedAwareInterface and set a new date time instance on the
     * dateUpdated property.
     *
     * @param EntityEvent $event
     *
     * @throws PersistenceException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (null === $entity || !$entity instanceof DateUpdatedAwareInterface) {
            $this->logger->debug(
                sprintf(
                    'Ignoring update date time for entity \'%s\': The entity does not implement \'%s\'',
                    $entityName,
                    DateUpdatedAwareInterface::class
                )
            );
            return;
        }

        $updateMode = $event->getParameters()->getParam(EntityEventOption::DATE_UPDATED_MODE, DateUpdateMode::ENABLED);
        $entityId = $entity->getId();

        if (DateUpdateMode::ENABLED !== $updateMode) {
            $this->logger->info(
                sprintf(
                    'The date time update of field \'dateUpdated\' '
                    . 'has been disabled for entity \'%s::%s\' using configuration option \'%s\'',
                    $entityName,
                    $entityId,
                    EntityEventOption::DATE_UPDATED_MODE
                )
            );
            return;
        }

        try {
            $dateUpdated = $this->dateTimeFactory->createDateTime();
        } catch (DateTimeFactoryException $e) {
            $errorMessage = sprintf(
                'Failed to create the update date time instance for entity \'%s::%s\': %s',
                $entityName,
                $entityId,
                $e->getMessage()
            );

            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        $entity->setDateUpdated($dateUpdated);

        $this->logger->info(
            sprintf(
                'The \'dateUpdated\' for entity \'%s::%s\' has been updated with new date time \'%s\'',
                $entityName,
                $entityId,
                $dateUpdated->format(\DateTime::ATOM)
            )
        );
    }
}
