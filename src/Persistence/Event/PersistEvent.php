<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
final class PersistEvent extends AbstractPersistEvent
{
    /**
     * @var EntityInterface
     */
    private $entity;

    /**
     * @param PersistServiceInterface $persistService
     * @param string                  $eventName
     * @param EntityInterface         $entity
     * @param array                   $params
     */
    public function __construct(
        PersistServiceInterface $persistService,
        string $eventName,
        EntityInterface $entity,
        array $params = []
    ) {
        parent::__construct($persistService, $eventName, $params);

        $this->entity = $entity;
    }

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
