<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\EventDispatcher\Event\NamedEvent;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
abstract class AbstractEntityEvent extends NamedEvent
{
    /**
     * @var string
     */
    private string $entityName;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param string                   $eventName
     * @param string                   $entityName
     * @param EntityManagerInterface   $entityManager
     * @param array<string|int, mixed> $params
     */
    public function __construct(
        string $eventName,
        string $entityName,
        EntityManagerInterface $entityManager,
        array $params = []
    ) {
        parent::__construct($eventName, $params);

        $this->entityName = $entityName;
        $this->entityManager = $entityManager;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
