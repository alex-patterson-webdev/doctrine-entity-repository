<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Persistence\Event\PersistEvent;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
class AggregateListener
{
    /**
     * @var callable[]
     */
    private $listeners;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param array           $listeners
     * @param LoggerInterface $logger
     */
    public function __construct(array $listeners, LoggerInterface $logger)
    {
        $this->listeners = $listeners;
        $this->logger = $logger;
    }

    /**
     * Perform event dispatch aggregation for all listeners within the collection.
     *
     * @param PersistEvent $event
     */
    public function __invoke(PersistEvent $event): void
    {
        foreach ($this->listeners as $listener) {
            $event = $listener($event);
        }
    }
}
