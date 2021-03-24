<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
class EntityErrorEvent extends AbstractEntityEvent
{
    /**
     * @var \Throwable|null
     */
    private ?\Throwable $exception;

    /**
     * @param string                   $eventName
     * @param PersistServiceInterface  $persistService
     * @param EntityManagerInterface   $entityManager
     * @param LoggerInterface          $logger
     * @param \Throwable|null          $exception
     * @param array<string|int, mixed> $params
     */
    public function __construct(
        string $eventName,
        PersistServiceInterface $persistService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ?\Throwable $exception = null,
        array $params = []
    ) {
        parent::__construct($eventName, $persistService, $entityManager, $logger, $params);

        $this->exception = $exception;
    }

    /**
     * @return bool
     */
    public function hasException(): bool
    {
        return isset($this->exception);
    }

    /**
     * @return \Throwable|null
     */
    public function getException(): ?\Throwable
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
