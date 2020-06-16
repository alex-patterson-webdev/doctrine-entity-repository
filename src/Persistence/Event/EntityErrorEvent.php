<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
class EntityErrorEvent extends AbstractEntityEvent
{
    /**
     * @var \Throwable
     */
    private \Throwable $exception;

    /**
     * @param string                 $eventName
     * @param string                 $entityName
     * @param EntityManagerInterface $entityManager
     * @param \Throwable             $exception
     * @param array                  $params
     */
    public function __construct(
        string $eventName,
        string $entityName,
        EntityManagerInterface $entityManager,
        \Throwable $exception,
        array $params = []
    ) {
        parent::__construct($eventName, $entityName, $entityManager, $params);

        $this->exception = $exception;
    }

    /**
     * @return \Throwable
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }

    /**
     * @param \Throwable $exception
     */
    public function setException(\Throwable $exception): void
    {
        $this->exception = $exception;
    }
}
