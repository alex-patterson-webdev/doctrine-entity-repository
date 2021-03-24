<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class ClearListener
{
    /**
     * Perform a clear of the current unit of works managed entities
     *
     * @param EntityEvent $event
     */
    public function __invoke(EntityEvent $event): void
    {
        $entityName = $event->getEventName();
        $logger = $event->getLogger();

        $clearMode = $event->getParam(EntityEventOption::CLEAR_MODE, ClearMode::DISABLED);
        if (ClearMode::ENABLED !== $clearMode) {
            $logger->debug(
                sprintf(
                    'Clearing operations are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    ClearMode::DISABLED,
                    EntityEventOption::CLEAR_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::CLEAR_MODE => ClearMode::DISABLED]
            );
            return;
        }

        $logger->debug(
            sprintf(
                'Clearing operations are enabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                $entityName,
                ClearMode::ENABLED,
                EntityEventOption::CLEAR_MODE
            ),
            ['entity_name' => $entityName, EntityEventOption::CLEAR_MODE => ClearMode::ENABLED]
        );

        $event->getEntityManager()->clear();

        $logger->debug(sprintf('Clear operation completed successfully for entity \'%s\'', $entityName));
    }
}
