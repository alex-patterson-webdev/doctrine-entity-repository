<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Arp\Entity\DateUpdatedAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateUpdatedListener extends AbstractDateTimeListener
{
    /**
     * @param PersistEvent $event
     *
     * @throws DateTimeFactoryException
     */
    public function __invoke(PersistEvent $event)
    {
        $entity = $event->getEntity();

        if (null === $entity || ! $entity instanceof DateUpdatedAwareInterface) {
            return;
        }

        $dateUpdated = $this->dateTimeFactory->createDateTime();

        $entity->setDateUpdated($dateUpdated);

        $this->logger->info(
            'The \'dateUpdated\' property was set to the current date time',
            compact('entity', 'dateUpdated')
        );
    }
}