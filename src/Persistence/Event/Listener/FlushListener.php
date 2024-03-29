<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Persistence\Event\AbstractEntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class FlushListener
{
    /**
     * Perform a flush of the current unit of work
     *
     * @param AbstractEntityEvent $event
     *
     * @throws PersistenceException
     */
    public function __invoke(AbstractEntityEvent $event): void
    {
        $flushMode = $event->getParam(EntityEventOption::FLUSH_MODE, FlushMode::ENABLED);
        $entityName = $event->getEntityName();

        if (FlushMode::ENABLED !== $flushMode) {
            $event->getLogger()->debug(
                sprintf(
                    'Flush operations are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    FlushMode::DISABLED,
                    EntityEventOption::FLUSH_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::FLUSH_MODE => FlushMode::DISABLED]
            );
            return;
        }

        $event->getLogger()->debug(
            sprintf(
                'Flush operations are enabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                $entityName,
                FlushMode::ENABLED,
                EntityEventOption::FLUSH_MODE
            )
        );

        $event->getPersistService()->flush();
    }
}
