<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\PersistEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistServiceException;
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
     * @param PersistEvent $event
     *
     * @throws PersistServiceException If the clear operation fails.
     */
    public function __invoke(PersistEvent $event): void
    {
        $clearMode = $event->getParameters()->getParam(PersistEventOption::CLEAR_MODE, ClearMode::NONE);

        if (ClearMode::NONE === $clearMode) {
            $this->logger->info(sprintf('Skipping clear operations with mode \'%s\'', $clearMode));
            return;
        }

        $persistService = $event->getPersistService();

        if (ClearMode::ALL === $clearMode) {
            $this->logger->info(sprintf('Performing clear operations with mode \'%s\'', $clearMode));
            $persistService->clear(null);
        } elseif (ClearMode::SINGLE === $clearMode) {
            $this->logger->info(sprintf('Performing clear operations with mode \'%s\'', $clearMode));
            $persistService->clear($persistService->getEntityName());
        }
    }
}
