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
    private $logger;

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
        $entity = $event->getEntity();

        if (null === $entity || !$entity instanceof DeleteAwareInterface) {
            return;
        }

        if ($entity->isDeleted()) {
            $this->logger->info(
                sprintf(
                    'Ignoring soft delete operations for already deleted entity \'%s::%s\'',
                    $event->getEntityName(),
                    $entity->getId()
                )
            );
            return;
        }

        $persistMode = $event->getParameters()->getParam(EntityEventOption::DELETE_MODE, DeleteMode::HARD);

        if (DeleteMode::SOFT !== $persistMode) {
            $this->logger->info(
                sprintf(
                    'Soft deleting has been disabled via configuration option \'%s\'',
                    EntityEventOption::DELETE_MODE
                )
            );
            return;
        }

        $this->logger->info(
            sprintf(
                'Performing \'%s\' delete for entity \'%s::%s\'',
                DeleteMode::SOFT,
                $event->getEntityName(),
                $entity->getId()
            )
        );

        $entity->setDeleted(true);
    }
}
