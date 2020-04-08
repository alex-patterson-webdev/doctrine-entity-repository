<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\EventDispatcher\Event\NamedEvent;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event
 */
abstract class AbstractPersistEvent extends NamedEvent
{
    /**
     * @var PersistServiceInterface
     */
    private $persistService;

    /**
     * @param PersistServiceInterface $persistService
     * @param string                  $eventName
     * @param array                   $params
     */
    public function __construct(PersistServiceInterface $persistService, string $eventName, array $params = [])
    {
        parent::__construct($eventName, $params);

        $this->persistService = $persistService;
    }

    /**
     * @return PersistServiceInterface
     */
    public function getPersistService(): PersistServiceInterface
    {
        return $this->persistService;
    }
}
