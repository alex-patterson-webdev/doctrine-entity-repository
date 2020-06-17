<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactoryInterface;
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
}
