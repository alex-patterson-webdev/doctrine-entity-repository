<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
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
     *
     * @throws PersistenceException
     */
    public function __invoke(EntityEvent $event): void
    {
        $entityName = $event->getEntityName();
        $entity = $event->getEntity();

        if (null === $entity) {
            $errorMessage = sprintf(
                'Unable to perform entity persist operation for entity of type \'%s\': The entity is null',
                $entityName
            );

            $this->logger->error($errorMessage, compact('entityName'));

            throw new PersistenceException($errorMessage);
        }

        $persistMode = $event->getParameters()->getParam(EntityEventOption::PERSIST_MODE, PersistMode::ENABLED);

        if (PersistMode::ENABLED !== $persistMode) {
            $this->logger->info(
                sprintf(
                    'Skipping persist operation for entity \'%s\' with persist mode \'%s\'',
                    $entityName,
                    $persistMode
                ),
                compact('entityName', 'persistMode')
            );
            return;
        }

        $this->logger->info(
            sprintf(
                'Performing persist operation for entity \'%s\' with persist mode \'%s\'',
                $entityName,
                $persistMode
            ),
            compact('entityName', 'persistMode')
        );

        $event->getEntityManager()->persist($entity);
    }
}
