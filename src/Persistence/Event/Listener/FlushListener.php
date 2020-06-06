<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class FlushListener
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
     * Perform a flush of the current unit of work.
     *
     * @param EntityEvent $event
     */
    public function __invoke(EntityEvent $event): void
    {
        $flushMode = $event->getParameters()->getParam(EntityEventOption::FLUSH_MODE, FlushMode::ENABLED);
        $entityName = $event->getEntityName();

        if (FlushMode::ENABLED !== $flushMode) {
            $this->logger->info(
                sprintf(
                    'Skipping flush operations for entity \'%s\' with mode \'%s\'',
                    $entityName,
                    $flushMode
                )
            );
            return;
        }

        $this->logger->info(sprintf('Performing flush operations with mode \'%s\'', $flushMode));
        $event->getEntityManager()->flush();
    }
}
