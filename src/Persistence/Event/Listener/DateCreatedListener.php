<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DateCreateMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DateCreatedAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
class DateCreatedListener extends AbstractDateTimeListener
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

        if (null === $entity || !$entity instanceof DateCreatedAwareInterface) {
            $this->logger->debug(
                sprintf(
                    'Ignoring update date time for entity \'%s\': The entity does not implement \'%s\'',
                    $entityName,
                    DateCreatedAwareInterface::class
                )
            );
            return;
        }

        $createMode = $event->getParameters()->getParam(EntityEventOption::DATE_CREATED_MODE, DateCreateMode::ENABLED);
        if (DateCreateMode::ENABLED !== $createMode) {
            $this->logger->info(
                sprintf(
                    'The date time update of field \'dateCreated\' '
                    . 'has been disabled for new entity \'%s\' using configuration option \'%s\'',
                    $entityName,
                    EntityEventOption::DATE_CREATED_MODE
                )
            );
            return;
        }

        $dateCreated = $this->createDateTime($entityName);
        $entity->setDateCreated($dateCreated);

        $this->logger->info(
            sprintf(
                'The \'dateCreated\' property for entity \'%s\' has been updated with new date time \'%s\'',
                $entityName,
                $dateCreated->format(\DateTime::ATOM)
            )
        );
    }
}
