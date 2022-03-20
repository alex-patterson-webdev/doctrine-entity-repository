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
     * @param DateTimeFactoryInterface $dateTimeFactory
     */
    public function __construct(DateTimeFactoryInterface $dateTimeFactory)
    {
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @param string          $entityName
     * @param LoggerInterface $logger
     *
     * @return \DateTimeInterface
     *
     * @throws PersistenceException
     */
    protected function createDateTime(string $entityName, LoggerInterface $logger): \DateTimeInterface
    {
        try {
            return $this->dateTimeFactory->createDateTime();
        } catch (DateTimeFactoryException $e) {
            $errorMessage = sprintf(
                'Failed to create the update date time instance for entity \'%s\': %s',
                $entityName,
                $e->getMessage()
            );

            $logger->error($errorMessage, ['entity_name' => $entityName, 'exception' => $e]);

            throw new PersistenceException($errorMessage);
        }
    }
}
