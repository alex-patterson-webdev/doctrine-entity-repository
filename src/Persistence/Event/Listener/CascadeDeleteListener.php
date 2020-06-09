<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\CascadeDeleteService;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CascadeDeleteService $cascadeDeleteService
     * @param LoggerInterface      $logger
     */
    public function __construct(CascadeDeleteService $cascadeDeleteService, LoggerInterface $logger)
    {
        $this->cascadeDeleteService = $cascadeDeleteService;
        $this->logger = $logger;
    }

    /**
     * @param EntityEvent $event
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entity = $event->getEntity();

        if (null === $entity) {
            $errorMessage= sprintf('Missing required entity in \'%s\'', static::class);
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        $entityName = $event->getEntityName();
        $parameters = $event->getParameters();

        $cascadeMode = $parameters->getParam(EntityEventOption::CASCADE_MODE, CascadeMode::ALL);

        if (CascadeMode::ALL !== $cascadeMode && CascadeMode::DELETE !== $cascadeMode) {
            $this->logger->info(
                sprintf(
                    'Ignoring cascade delete operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );
            return;
        }

        $this->logger->info(sprintf('Performing cascade delete operations for entity \'%s\'', $entityName));

        $deleteOptions = $parameters->getParam(EntityEventOption::CASCADE_DELETE_OPTIONS, []);
        $deleteCollectionOptions = $parameters->getParam(EntityEventOption::CASCADE_DELETE_COLLECTION_OPTIONS, []);

        $this->cascadeDeleteService->deleteAssociations(
            $event->getEntityManager(),
            $entityName,
            $entity,
            $deleteOptions,
            $deleteCollectionOptions
        );
    }
}
