<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\Entity\EntityInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
class CollectionEvent extends AbstractEntityEvent
{
    /**
     * @var iterable<EntityInterface>
     */
    private iterable $collection = [];

    /**
     * @return int
     */
    public function getCount(): int
    {
        $collection = ($this->collection instanceof \Traversable)
            ? iterator_to_array($this->collection)
            : $this->collection;

        return count($collection);
    }

    /**
     * @return iterable<EntityInterface>
     */
    public function getCollection(): iterable
    {
        return $this->collection;
    }

    /**
     * @param iterable<EntityInterface> $collection
     */
    public function setCollection(iterable $collection): void
    {
        $this->collection = $collection;
    }
}
