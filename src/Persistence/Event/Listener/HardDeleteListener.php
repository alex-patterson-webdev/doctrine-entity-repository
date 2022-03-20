<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\Entity\DeleteAwareInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class HardDeleteListener
{
    /**
     * Perform the entity hard deletion operation.
     *
     * @param EntityEvent $event
     */
    public function __invoke(EntityEvent $event): void
    {
        $entity = $event->getEntity();

        if (null === $entity) {
            return;
        }

        $logger = $event->getLogger();
        $entityName = $event->getEntityName();

        $deleteMode = $event->getParam(
            EntityEventOption::DELETE_MODE,
            ($entity instanceof DeleteAwareInterface ? DeleteMode::SOFT : DeleteMode::HARD)
        );

        if (DeleteMode::HARD !== $deleteMode) {
            $logger->debug(
                sprintf(
                    'Delete operations are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    $deleteMode,
                    EntityEventOption::DELETE_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::DELETE_MODE => $deleteMode]
            );
            return;
        }

        $event->getEntityManager()->remove($entity);

        $logger->debug(sprintf('Successfully performed the delete operation for entity \'%s\'', $entityName));
    }
}
