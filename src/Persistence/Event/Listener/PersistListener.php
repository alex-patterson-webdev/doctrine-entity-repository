<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class PersistListener
{
    /**
     * @param EntityEvent $event
     *
     * @throws PersistenceException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (null === $entity) {
            $errorMessage = sprintf(
                'Unable to perform entity persist operation for entity of type \'%s\': The entity is null',
                $entityName
            );

            $event->getLogger()->error($errorMessage, compact('entityName'));

            throw new PersistenceException($errorMessage);
        }

        $persistMode = $event->getParam(EntityEventOption::PERSIST_MODE, PersistMode::ENABLED);
        if (PersistMode::ENABLED !== $persistMode) {
            $event->getLogger()->debug(
                sprintf(
                    'Persist operations are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    PersistMode::DISABLED,
                    EntityEventOption::PERSIST_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::PERSIST_MODE => PersistMode::DISABLED]
            );
            return;
        }

        $event->getLogger()->debug(
            sprintf(
                'Flush operations are enabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                $entityName,
                PersistMode::ENABLED,
                EntityEventOption::PERSIST_MODE
            )
        );

        $event->getPersistService()->persist($entity);
    }
}
