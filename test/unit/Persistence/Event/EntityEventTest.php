<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\Parameters;
use Arp\EventDispatcher\Resolver\EventNameAwareInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event
 */
class EntityEventTest extends TestCase
{
    /**
     * @var string
     */
    private string $entityName;

    /**
     * @var string
     */
    private string $eventName;

    /**
     * @var PersistServiceInterface&MockObject
     */
    private $persistService;

    /**
     * @var EntityManagerInterface&MockObject
     */
    private $entityManager;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->entityName = EntityInterface::class;

        $this->eventName = EntityEventName::CREATE;

        $this->persistService = $this->createMock(PersistServiceInterface::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the entity event implements the EventNameAwareInterface
     */
    public function testEntityEventImplementsEventNameAwareInterface(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        $this->assertInstanceOf(EventNameAwareInterface::class, $entityEvent);
    }

    /**
     * Assert that the event name can be returned via getEventName()
     */
    public function testGetEventNameWillReturnNamedEvent(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        $this->assertSame($this->eventName, $entityEvent->getEventName());
    }

    /**
     * Assert that the event name can be returned via getEntityName()
     */
    public function testGetEntityNameWillReturnEntityFullyQualifiedClassName(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        $this->persistService->expects($this->once())
            ->method('getEntityName')
            ->willReturn($this->entityName);

        $this->assertSame($this->entityName, $entityEvent->getEntityName());
    }

    /**
     * Assert that the entity manager can be returned via getEntityManager()
     */
    public function testGetEntityManagerWillReturnEntityManager(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        $this->assertSame($this->entityManager, $entityEvent->getEntityManager());
    }

    /**
     * Assert that the event parameters instance can be retrieved via getParameters()
     */
    public function testGetParametersReturnsParametersCollection(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        $this->assertInstanceOf(Parameters::class, $entityEvent->getParameters());
    }

    /**
     * Assert that hasEntity() will return false if no entity is defined
     */
    public function testHasEntityWillReturnFalseForNullEntity(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        $this->assertFalse($entityEvent->hasEntity());
    }

    /**
     * Assert that hasEntity() will return false if no entity is defined
     */
    public function testHasEntityWillReturnTrueWhenEntityIsSet(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entityEvent->setEntity($entity);

        $this->assertTrue($entityEvent->hasEntity());
    }

    /**
     * Asset that an entity can be set/get from setEntity() and getEntity()
     */
    public function testGetSetEntity(): void
    {
        $entityEvent = new EntityEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entityEvent->setEntity($entity);

        $this->assertSame($entity, $entityEvent->getEntity());
    }
}
