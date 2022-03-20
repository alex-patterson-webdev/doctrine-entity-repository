<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\CascadeSaveService;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\AggregateEntityInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class CascadeSaveListener
{
    /**
     * @var CascadeSaveService
     */
    private CascadeSaveService $cascadeSaveService;

    /**
     * @param CascadeSaveService $cascadeSaveService
     */
    public function __construct(CascadeSaveService $cascadeSaveService)
    {
        $this->cascadeSaveService = $cascadeSaveService;
    }

    /**
     * Perform the cascading save operations for the persisted entity.
     *
     * @param EntityEvent $event
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entity = $event->getEntity();

        if (null === $entity || !$entity instanceof AggregateEntityInterface) {
            return;
        }

        $cascadeMode = $event->getParam(EntityEventOption::CASCADE_MODE, CascadeMode::ALL);
        $entityName = $event->getEntityName();

        if (CascadeMode::ALL !== $cascadeMode && CascadeMode::SAVE !== $cascadeMode) {
            $event->getLogger()->debug(
                sprintf(
                    'Ignoring cascade save operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );
            return;
        }

        $event->getLogger()->debug(
            sprintf('Performing cascade save operations for entity \'%s\'', $entityName)
        );

        $this->cascadeSaveService->saveAssociations(
            $event->getEntityManager(),
            $entityName,
            $entity,
            $event->getParam(EntityEventOption::CASCADE_SAVE_OPTIONS, []),
            $event->getParam(EntityEventOption::CASCADE_SAVE_COLLECTION_OPTIONS, [])
        );
    }
}
