<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Constant\PersistEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistServiceException;
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
    private $logger;

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
     * @param PersistEvent $event
     *
     * @throws PersistServiceException If the flush operation fails
     */
    public function __invoke(PersistEvent $event): void
    {
        $flushMode = $event->getParameters()->getParam(PersistEventOption::FLUSH_MODE, FlushMode::ALL);

        if (FlushMode::NONE === $flushMode) {
            $this->logger->info(sprintf('Skipping flush operations with mode \'%s\'', $flushMode));
            return;
        }

        $persistService = $event->getPersistService();

        if (FlushMode::ALL === $flushMode) {
            $this->logger->info(sprintf('Performing flush operations with mode \'%s\'', $flushMode));
            $persistService->flush();
        } elseif (FlushMode::SINGLE === $flushMode) {
            $this->logger->info(sprintf('Performing flush operations with mode \'%s\'', $flushMode));
            $persistService->flush($event);
        }
    }
}
