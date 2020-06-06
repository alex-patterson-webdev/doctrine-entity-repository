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
final class DateTimeListener implements AggregateListenerInterface
{
    /**
     * @var DateCreatedListener
     */
    private DateCreatedListener $dateCreatedListener;

    /**
     * @var DateUpdatedListener
     */
    private DateUpdatedListener $dateUpdatedListener;

    /**
     * @var DateDeletedListener
     */
    private DateDeletedListener $dateDeletedListener;

    /**
     * @param DateCreatedListener $dateCreatedListener
     * @param DateUpdatedListener $dateUpdatedListener
     * @param DateDeletedListener $dateDeletedListener
     */
    public function __construct(
        DateCreatedListener $dateCreatedListener,
        DateUpdatedListener $dateUpdatedListener,
        DateDeletedListener $dateDeletedListener
    ) {
        $this->dateCreatedListener = $dateCreatedListener;
        $this->dateUpdatedListener = $dateUpdatedListener;
        $this->dateDeletedListener = $dateDeletedListener;
    }

    /**
     * Add event listeners to the provided listener collection,
     *
     * @param AddListenerAwareInterface $collection
     *
     * @throws EventListenerException
     */
    public function addListeners(AddListenerAwareInterface $collection): void
    {
        $collection->addListenerForEvent(EntityEventName::CREATE, $this->dateCreatedListener, 10);
        $collection->addListenerForEvent(EntityEventName::UPDATE, $this->dateUpdatedListener, 10);
        $collection->addListenerForEvent(EntityEventName::DELETE, $this->dateDeletedListener, 10);
    }
}
