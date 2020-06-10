<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\Entity\EntityInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
class EntityEvent extends AbstractEntityEvent
{
    /**
     * @var EntityInterface|null
     */
    private $entity;

    /**
     * @return bool
     */
    public function hasEntity(): bool
    {
        return isset($this->entity);
    }

    /**
     * @return EntityInterface|null
     */
    public function getEntity(): ?EntityInterface
    {
        return $this->entity;
    }

    /**
     * @param EntityInterface|null $entity
     */
    public function setEntity(?EntityInterface $entity): void
    {
        $this->entity = $entity;
    }
}
