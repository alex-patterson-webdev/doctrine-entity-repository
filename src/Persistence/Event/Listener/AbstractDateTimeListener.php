<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
abstract class AbstractDateTimeListener
{
    /**
     * @var DateTimeFactoryInterface
     */
    protected DateTimeFactoryInterface $dateTimeFactory;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param DateTimeFactoryInterface $dateTimeFactory
     * @param LoggerInterface $logger
     */
    public function __construct(DateTimeFactoryInterface $dateTimeFactory, LoggerInterface $logger)
    {
        $this->dateTimeFactory = $dateTimeFactory;
        $this->logger = $logger;
    }

    /**
     * @param string $entityName
     *
     * @return \DateTimeInterface
     *
     * @throws PersistenceException
     */
    protected function createDateTime(string $entityName): \DateTimeInterface
    {
        try {
            return $this->dateTimeFactory->createDateTime();
        } catch (DateTimeFactoryException $e) {
            $errorMessage = sprintf(
                'Failed to create the update date time instance for entity \'%s\': %s',
                $entityName,
                $e->getMessage()
            );

            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }
    }
}
