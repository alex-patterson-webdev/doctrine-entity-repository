<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class EntitySaveListener implements AggregateListenerInterface
{
    /**
     * @var callable
     */
    private $persistListener;

    /**
     * @var callable
     */
    private $deleteListener;

    /**
     * @var callable
     */
    private $flushListener;

    /**
     * @var callable
     */
    private $clearListener;

    /**
     * @param callable $persistListener
     * @param callable $deleteListener
     * @param callable $flushListener
     * @param callable $clearListener
     */
    public function __construct(
        callable $persistListener,
        callable $deleteListener,
        callable $flushListener,
        callable $clearListener
    ) {
        $this->persistListener = $persistListener;
        $this->deleteListener = $deleteListener;
        $this->flushListener = $flushListener;
        $this->clearListener = $clearListener;
    }

    /**
     * @param AddListenerAwareInterface $collection
     *
     * @throws EventListenerException
     */
    public function addListeners(AddListenerAwareInterface $collection): void
    {
        $collection->addListenersForEvent(
            EntityEventName::CREATE,
            [
                $this->persistListener,
                $this->flushListener,
                $this->clearListener,
            ]
        );

        $collection->addListenersForEvent(
            EntityEventName::UPDATE,
            [
                $this->flushListener,
                $this->clearListener,
            ]
        );

        $collection->addListenersForEvent(
            EntityEventName::DELETE,
            [
                $this->deleteListener,
                $this->flushListener,
                $this->clearListener,
            ]
        );
    }
}
