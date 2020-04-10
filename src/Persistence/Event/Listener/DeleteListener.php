<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DeleteListener implements AggregateListenerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FlushListener
     */
    private $flushListener;

    /**
     * @var ClearListener
     */
    private $clearListener;

    /**
     * @var HardDeleteListener|null
     */
    private $hardDeleteListener;

    /**
     * @var SoftDeleteListener|null
     */
    private $softDeleteListener;

    /**
     * @param LoggerInterface $logger
     * @param FlushListener $flushListener
     * @param ClearListener $clearListener
     * @param HardDeleteListener|null $hardDeleteListener
     * @param SoftDeleteListener|null $softDeleteListener
     */
    public function __construct(
        LoggerInterface $logger,
        FlushListener $flushListener,
        ClearListener $clearListener,
        ?HardDeleteListener $hardDeleteListener,
        ?SoftDeleteListener $softDeleteListener
    ) {
        $this->logger = $logger;
        $this->flushListener = $flushListener;
        $this->clearListener = $clearListener;
        $this->hardDeleteListener = $hardDeleteListener;
        $this->softDeleteListener = $softDeleteListener;
    }

    /**
     * Add multiple listeners to the provided collection.
     *
     * @param AddListenerAwareInterface $collection
     *
     * @throws EventListenerException
     */
    public function addListeners(AddListenerAwareInterface $collection): void
    {
        if (null === $this->hardDeleteListener && null === $this->softDeleteListener) {
            $this->logger->warning(
                sprintf('Both the hard and soft delete listeners are missing in \'%s\'', get_class($this))
            );
            return;
        }

        if (null !== $this->softDeleteListener) {
            $this->logger->info('Registering soft delete listener');
            $collection->addListenerForEvent(EntityEventName::DELETE, $this->softDeleteListener, 1);
        }

        if (null !== $this->hardDeleteListener) {
            $this->logger->info('Registering hard delete listener');
            $collection->addListenerForEvent(EntityEventName::DELETE, $this->hardDeleteListener, 1);
        }

        $collection->addListenerForEvent(EntityEventName::DELETE, $this->flushListener, 1);
        $collection->addListenerForEvent(EntityEventName::DELETE, $this->clearListener, -1);
    }
}
