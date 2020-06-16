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
    private LoggerInterface $logger;

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
        $clearMode = $event->getParameters()->getParam(EntityEventOption::CLEAR_MODE, ClearMode::DISABLED);

        if (ClearMode::ENABLED !== $clearMode) {
            $this->logger->info(
                sprintf(
                    'Clear mode is disabled for entity \'%s\'; skipping entity manager clear operations',
                    $event->getEntityName()
                )
            );
            return;
        }

        $this->logger->info(
            sprintf(
                'Performing entity manager clear operations for entity class \'%s\'',
                $event->getEntityName()
            )
        );

        $entityManager = $event->getEntityManager();
        $entityManager->clear();
    }
}
