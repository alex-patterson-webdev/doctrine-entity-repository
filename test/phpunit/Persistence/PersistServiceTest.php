<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Persistence\PersistService;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\EventDispatcher;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence
 */
final class PersistServiceTest extends TestCase
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var EventDispatcher|MockObject
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Set up the test case dependencies.
     */
    public function setUp(): void
    {
        $this->entityName = EntityInterface::class;

        $this->entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $this->eventDispatcher = $this->getMockForAbstractClass(EventDispatcherInterface::class);

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the class implements PersistServiceInterface.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::__construct
     */
    public function testImplementsPersistServiceInterface(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->assertInstanceOf(PersistServiceInterface::class, $persistService);
    }
}
