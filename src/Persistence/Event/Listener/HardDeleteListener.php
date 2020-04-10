<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DeleteAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class HardDeleteListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Perform the entity hard deletion operation.
     *
     * @param EntityEvent $event
     *
     * @throws PersistenceException
     */
    public function __invoke(EntityEvent $event)
    {
        $entity = $event->getEntity();

        if (null === $entity) {
            return;
        }

        $entityName = $event->getEntityName();
        $entityId = $entity->getId();

        $deleteMode = $event->getParameters()->getParam(EntityEventOption::DELETE_MODE);

        if (DeleteMode::SOFT === $deleteMode) {
            $this->logger->info(
                sprintf(
                    'Delete mode \'%s\' detected : Skipping hard delete operations for entity \'%s::%s\'',
                    $deleteMode,
                    $entityName,
                    $entityId
                )
            );

            if (!$entity instanceof DeleteAwareInterface) {
                $errorMessage = sprintf(
                    'The delete mode \'%s\' is invalid for entity \'%s\'; The entity must implement interface \'%s\'',
                    $deleteMode,
                    $entityName,
                    DeleteAwareInterface::class
                );

                $this->logger->warning($errorMessage);

                throw new PersistenceException($errorMessage);
            }

            return;
        }

        try {
            $event->getEntityManager()->remove($entity);

            $this->logger->info(
                sprintf(
                    'Successfully performed the hard delete operation for entity \'%s::%s\'',
                    $entityName,
                    $entityId
                )
            );
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Failed to perform delete of entity \'%s::%s\' : %s',
                $entityName,
                $entityId,
                $e->getMessage()
            );

            $this->logger->error(
                $errorMessage,
                ['exception' => $e, 'entity_name' => $entityName, 'id' => $entityId]
            );

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }
}
