<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\PersistEventOption;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistServiceException;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class PersistListener
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
     * Register the entity for persistence.
     *
     * @param PersistEvent $event
     *
     * @throws PersistServiceException If the entity cannot be found or persisted.
     */
    public function __invoke(PersistEvent $event): void
    {
        $entity = $event->getEntity();

        if (null === $entity) {
            $errorMessage = 'Unable to perform entity persist operation : The entity is undefined';

            $this->logger->error($errorMessage, compact('event'));

            throw new PersistServiceException($errorMessage);
        }

        $persistMode = $event->getParameters()->getParam(PersistEventOption::PERSIST_MODE, PersistMode::ENABLED);

        if (PersistMode::ENABLED !== $persistMode) {
            $this->logger->info(
                sprintf('Skipping entity persist operation with persist mode \'%s\'', $persistMode),
                compact('entity', 'persistMode')
            );
            return;
        }

        $this->logger->info('Performing entity persist operations', compact('entity'));

        $event->getPersistService()->persist($entity);
    }
}
