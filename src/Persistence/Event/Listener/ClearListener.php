<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class ClearListener
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
     * Perform a clear of the current unit of work's identity map.
     *
     * @param EntityEvent $event
     */
    public function __invoke(EntityEvent $event): void
    {
        $clearMode = $event->getParameters()->getParam(EntityEventOption::CLEAR_MODE, ClearMode::NONE);
        $entityName = $event->getEntityName();

        if (ClearMode::NONE === $clearMode) {
            $this->logger->info(
                sprintf(
                    'Skipping clear operations for entity class \'%s\' with mode \'%s\'',
                    $entityName,
                    $clearMode
                )
            );
            return;
        }

        $entityManager = $event->getEntityManager();

        if (ClearMode::ALL === $clearMode) {
            $this->logger->info(sprintf('Performing clear operations for all entities with mode \'%s\'', $clearMode));
            $entityManager->clear(null);
        } elseif (ClearMode::SINGLE === $clearMode) {
            $this->logger->info(
                sprintf(
                    'Performing clear operations for entity class \'%s\' mode \'%s\'',
                    $entityName,
                    $clearMode
                )
            );
            $entityManager->clear($entityName);
        }
    }
}
