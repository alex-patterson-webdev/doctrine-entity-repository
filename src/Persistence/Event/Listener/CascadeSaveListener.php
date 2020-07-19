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
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CascadeSaveService $cascadeSaveService
     * @param LoggerInterface    $logger
     */
    public function __construct(CascadeSaveService $cascadeSaveService, LoggerInterface $logger)
    {
        $this->cascadeSaveService = $cascadeSaveService;
        $this->logger = $logger;
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

        $entityName = $event->getEntityName();
        $parameters = $event->getParameters();

        $cascadeMode = $parameters->getParam(EntityEventOption::CASCADE_MODE, CascadeMode::ALL);

        if (CascadeMode::ALL !== $cascadeMode && CascadeMode::SAVE !== $cascadeMode) {
            $this->logger->info(
                sprintf(
                    'Ignoring cascade save operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );
            return;
        }

        $this->logger->info(
            sprintf('Performing cascade save operations for entity \'%s\'', $entityName)
        );

        $saveOptions = $parameters->getParam(EntityEventOption::CASCADE_SAVE_OPTIONS, []);
        $saveCollectionOptions = $parameters->getParam(EntityEventOption::CASCADE_SAVE_COLLECTION_OPTIONS, []);

        $this->cascadeSaveService->saveAssociations(
            $event->getEntityManager(),
            $entityName,
            $entity,
            $saveOptions,
            $saveCollectionOptions
        );
    }
}
