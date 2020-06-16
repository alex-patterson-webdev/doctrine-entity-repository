<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Constant\DateCreatedMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\Entity\DateCreatedAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
class DateCreatedListener extends AbstractDateTimeListener
{
    /**
     * @param EntityEvent $event
     *
     * @throws DateTimeFactoryException
     */
    public function __invoke(EntityEvent $event)
    {
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (!$entity instanceof DateCreatedAwareInterface) {
            $this->logger->debug(
                sprintf(
                    'Ignoring created date operations : \'%s\' does not implement \'%s\'.',
                    $entityName,
                    DateCreatedAwareInterface::class
                )
            );
            return;
        }

        $mode = $event->getParameters()->getParam(EntityEventOption::DATE_CREATED_MODE, DateCreatedMode::ENABLED);

        if (DateCreatedMode::ENABLED !== $mode) {
            $this->logger->info(
                sprintf(
                    'Ignoring created date operations : \'%s\' has a date create mode set to \'%s\'.',
                    $entityName,
                    $mode
                )
            );
            return;
        }


        $dateCreated = $this->dateTimeFactory->createDateTime();
        $entity->setDateCreated($dateCreated);

        $this->logger->info(
            sprintf(
                'Setting new created date \'%s\' for entity of type \'%s\'',
                $dateCreated->format(\DateTime::ATOM),
                $entityName
            )
        );
    }
}
