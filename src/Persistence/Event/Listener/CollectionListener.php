<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Wraps multiple listeners in a collection which can then be registered as one listener. You should use this
 * class when you wish to add multiple event listeners to the same event with the same priority.
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
class CollectionListener
{
    /**
     * @var callable[]
     */
    private array $listeners;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param callable[]      $listeners
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
     * @param EntityEvent $event
     *
     * @throws InvalidArgumentException
     */
    public function __invoke(EntityEvent $event): void
    {
        $this->logger->info(
            sprintf(
                'Performing execution of collection listener \'%s\' for event \'%s\' with entity \'%s\'',
                get_class($this),
                $event->getEventName(),
                $event->getEntityName()
            )
        );

        foreach ($this->listeners as $index => $listener) {
            if (!is_callable($listener)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The listener provided at index \'%d\' of collection listener \'%s\' is not callable',
                        $index,
                        gettype($listener)
                    )
                );
            }

            $this->logger->info(
                sprintf(
                    'Executing listener \'%s\' for event \'%s\' with entity \'%s\'',
                    is_object($listener) ? get_class($listener) : gettype($listener),
                    $event->getEventName(),
                    $event->getEntityName()
                )
            );

            $listener($event);
        }
    }
}
