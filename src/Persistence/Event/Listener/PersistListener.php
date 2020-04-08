<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\PersistEventOption;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistServiceException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class PersistListener
{
    /**
     * @param PersistEvent $event
     *
     * @throws PersistServiceException
     */
    public function __invoke(PersistEvent $event): void
    {
        if ($event->hasEntity()) {
            return;
        }

        $persistMode = $event->getParameters()->getParam(PersistEventOption::PERSIST_MODE, PersistMode::ENABLED);

        if (PersistMode::ENABLED !== $persistMode) {
            return;
        }

        $event->getPersistService()->persist($event->getEntity());
    }
}
