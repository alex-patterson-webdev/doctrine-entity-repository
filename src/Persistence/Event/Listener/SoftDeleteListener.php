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
final class SoftDeleteListener
{
    /**
     * @param EntityEvent $event
     */
    public function __invoke(EntityEvent $event): void
    {
        $logger = $event->getLogger();
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (null === $entity || !$entity instanceof DeleteAwareInterface) {
            $logger->debug(sprintf('Soft delete operations are not available for entity \'%s\'', $entityName));
            return;
        }

        if ($entity->isDeleted()) {
            $logger->warning(
                sprintf(
                    'Ignoring soft delete operations for already deleted entity \'%s::%s\'',
                    $entityName,
                    $entity->getId()
                )
            );
            return;
        }

        $deleteMode = $event->getParameters()->getParam(EntityEventOption::DELETE_MODE, DeleteMode::SOFT);

        if (DeleteMode::SOFT !== $deleteMode) {
            $logger->debug(
                sprintf(
                    'Soft delete operations are disabled for entity \'%s\' '
                    . 'using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    $deleteMode,
                    EntityEventOption::DELETE_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::DELETE_MODE => $deleteMode]
            );
            return;
        }

        $entity->setDeleted(true);
        $logger->debug(
            sprintf('Soft delete operations completed successfully for entity \'%s\'', $entityName)
        );
    }
}
