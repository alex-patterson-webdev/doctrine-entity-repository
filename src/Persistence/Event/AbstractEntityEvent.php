<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\EventDispatcher\Event\NamedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
abstract class AbstractEntityEvent extends NamedEvent
{
    /**
     * @var PersistServiceInterface
     */
    private PersistServiceInterface $persistService;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param string                   $eventName
     * @param PersistServiceInterface  $persistService
     * @param EntityManagerInterface   $entityManager
     * @param LoggerInterface          $logger
     * @param array<string|int, mixed> $params
     */
    public function __construct(
        string $eventName,
        PersistServiceInterface $persistService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        array $params = []
    ) {
        $this->persistService = $persistService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;

        parent::__construct($eventName, $params);
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->persistService->getEntityName();
    }

    /**
     * @return PersistServiceInterface
     */
    public function getPersistService(): PersistServiceInterface
    {
        return $this->persistService;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParam(string $name, $default = null)
    {
        return $this->getParameters()->getParam($name, $default);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
