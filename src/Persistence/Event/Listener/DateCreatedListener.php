<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\Exception\DateTimeFactoryException;
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
        $entity = $event->getEntity();

        if (null === $entity || ! $entity instanceof DateCreatedAwareInterface) {
            return;
        }

        $dateCreated = $this->dateTimeFactory->createDateTime();

        $entity->setDateCreated($dateCreated);

        $this->logger->info(
            'The \'dateCreated\' property was set to the current date time',
            compact('entity', 'dateCreated')
        );
    }
}
