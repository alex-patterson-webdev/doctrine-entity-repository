<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use Arp\EventDispatcher\Resolver\EventNameAwareInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
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
     * @var EntityManagerInterface|MockObject
     */
    private $entityManager;

    /**
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->entityName = EntityInterface::class;

        $this->eventName = EntityEventName::CREATE;

        $this->entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);
    }

    /**
     * Assert that the entity event implements the EventNameAwareInterface.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::__construct
     */
    public function testEntityEventImplementsEventNameAwareInterface(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        $this->assertInstanceOf(EventNameAwareInterface::class, $entityEvent);
    }

    /**
     * Assert that the event name can be returned via getEventName().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::getEventName
     */
    public function testGetEventNameWillReturnNamedEvent(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        $this->assertSame($this->eventName, $entityEvent->getEventName());
    }

    /**
     * Assert that the event name can be returned via getEntityName().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::getEntityName
     */
    public function testGetEntityNameWillReturnEntityFullyQualifiedClassName(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        $this->assertSame($this->entityName, $entityEvent->getEntityName());
    }

    /**
     * Assert that the entity manager can be returned via getEntityManager().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::getEntityManager
     */
    public function testGetEntityManagerWillReturnEntityManager(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        $this->assertSame($this->entityManager, $entityEvent->getEntityManager());
    }

    /**
     * Assert that the event parameters instance can be retrieved via getParameters().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::getParameters
     */
    public function testGetParametersReturnsParametersCollection(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        $this->assertInstanceOf(ParametersInterface::class, $entityEvent->getParameters());
    }

    /**
     * Assert that hasEntity() will return false if no entity is defined.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::hasEntity
     */
    public function testHasEntityWillReturnFalseForNullEntity(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        $this->assertFalse($entityEvent->hasEntity());
    }

    /**
     * Assert that hasEntity() will return false if no entity is defined.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::hasEntity
     */
    public function testHasEntityWillReturnTrueWhenEntityIsSet(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entityEvent->setEntity($entity);

        $this->assertTrue($entityEvent->hasEntity());
    }

    /**
     * Asset that an entity can be set/get from setEntity() and getEntity()
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::getEntity
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent::setEntity
     */
    public function testGetSetEntity(): void
    {
        $entityEvent = new EntityEvent($this->eventName, $this->entityName, $this->entityManager);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entityEvent->setEntity($entity);

        $this->assertSame($entity, $entityEvent->getEntity());
    }
}
