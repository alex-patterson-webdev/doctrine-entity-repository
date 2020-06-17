<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\Entity\DeleteAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class SoftDeleteListener
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param EntityEvent $event
     */
    public function __invoke(EntityEvent $event)
    {
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (null === $entity || !$entity instanceof DeleteAwareInterface) {
            $this->logger->info(
                sprintf(
                    'Ignoring soft delete for entity \'%s\': The entity value is either null '
                    . 'or this entity has not configured to be able to perform soft deletes',
                    $entityName
                )
            );
            return;
        }

        if ($entity->isDeleted()) {
            $this->logger->info(
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
            $this->logger->info(
                sprintf(
                    'Soft deleting has been disabled for entity \'%s\' via configuration option \'%s\'',
                    $entityName,
                    EntityEventOption::DELETE_MODE
                )
            );
            return;
        }

        $this->logger->info(
            sprintf(
                'Performing \'%s\' delete for entity \'%s::%s\'',
                DeleteMode::SOFT,
                $entityName,
                $entity->getId()
            )
        );

        $entity->setDeleted(true);
    }
}
