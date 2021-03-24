<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\CascadeDeleteService;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class CascadeDeleteListener
{
    /**
     * @var CascadeDeleteService
     */
    private CascadeDeleteService $cascadeDeleteService;

    /**
     * @param CascadeDeleteService $cascadeDeleteService
     */
    public function __construct(CascadeDeleteService $cascadeDeleteService)
    {
        $this->cascadeDeleteService = $cascadeDeleteService;
    }

    /**
     * @param EntityEvent $event
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entityName = $event->getEntityName();
        $logger = $event->getLogger();
        $entity = $event->getEntity();

        if (null === $entity) {
            $errorMessage = sprintf('Missing required entity in \'%s\'', self::class);
            $logger->error($errorMessage, ['entity_name' => $entityName]);

            throw new PersistenceException($errorMessage);
        }

        $cascadeMode = $event->getParam(EntityEventOption::CASCADE_MODE, CascadeMode::ALL);

        if (CascadeMode::ALL !== $cascadeMode && CascadeMode::DELETE !== $cascadeMode) {
            $logger->debug(
                sprintf(
                    'The cascade delete operations are disabled for entity \'%s\' '
                    . 'using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    $cascadeMode,
                    EntityEventOption::CASCADE_MODE
                )
            );
            return;
        }

        $event->getLogger()->debug(
            sprintf(
                'Performing cascade delete operations for entity \'%s\' using \'%s\' configuration setting \'%s\'',
                $entityName,
                $cascadeMode,
                EntityEventOption::CASCADE_MODE
            ),
        );

        $this->cascadeDeleteService->deleteAssociations(
            $event->getEntityManager(),
            $entityName,
            $entity,
            $event->getParam(EntityEventOption::CASCADE_DELETE_OPTIONS, []),
            $event->getParam(EntityEventOption::CASCADE_DELETE_COLLECTION_OPTIONS, [])
        );
    }
}
