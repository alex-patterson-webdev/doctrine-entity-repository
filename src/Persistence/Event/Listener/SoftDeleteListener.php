<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Arp\Entity\DeleteAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class SoftDeleteListener
{
    /**
     * @param PersistEvent $event
     */
    public function __invoke(PersistEvent $event)
    {
        $entity = $event->getEntity();

        if (null === $entity) {
            return;
        }

        if ($entity instanceof DeleteAwareInterface) {
            $entity->setDeleted(true);
        }
    }
}
