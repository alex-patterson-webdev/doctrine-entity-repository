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
    private $dateCreatedListener;

    /**
     * @var DateUpdatedListener
     */
    private $dateUpdatedListener;

    /**
     * @param DateCreatedListener $dateCreatedListener
     * @param DateUpdatedListener $dateUpdatedListener
     */
    public function __construct(DateCreatedListener $dateCreatedListener, DateUpdatedListener $dateUpdatedListener)
    {
        $this->dateCreatedListener = $dateCreatedListener;
        $this->dateUpdatedListener = $dateUpdatedListener;
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
        $collection->addListenerForEvent(EntityEventName::CREATE, $this->dateCreatedListener, 1);
        $collection->addListenerForEvent(EntityEventName::UPDATE, $this->dateUpdatedListener, 1);
    }
}
