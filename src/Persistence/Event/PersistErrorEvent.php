<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
class PersistErrorEvent extends AbstractPersistEvent
{
    /**
     * @var \Throwable
     */
    private $exception;

    /**
     * @param PersistServiceInterface $persistService
     * @param string                  $eventName
     * @param \Throwable              $exception
     * @param array                   $params
     */
    public function __construct(
        PersistServiceInterface $persistService,
        string $eventName,
        \Throwable $exception,
        array $params = []
    ) {
        parent::__construct($persistService, $eventName, $params);

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
