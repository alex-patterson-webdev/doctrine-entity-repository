<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\Entity\DateDeletedAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
class DateDeletedListener extends AbstractDateTimeListener
{
    /**
     * @param EntityEvent $event
     *
     * @throws DateTimeFactoryException
     */
    public function __invoke(EntityEvent $event)
    {
        $entity = $event->getEntity();

        if (null === $entity || ! $entity instanceof DateDeletedAwareInterface) {
            return;
        }

        $dateDeleted = $this->dateTimeFactory->createDateTime();

        $entity->setDateDeleted($dateDeleted);

        $this->logger->info(
            'The \'dateDeleted\' property was set to the current date time',
            compact('entity', 'dateDeleted')
        );
    }
}
