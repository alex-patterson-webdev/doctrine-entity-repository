<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\EntityValidationListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\InvalidArgumentException;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\EntityValidationListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class EntityValidationListenerTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * Set up the test case dependencies.
     */
    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the class implements AggregateListenerInterface
     */
    public function testImplementsAggregateListenerInterface(): void
    {
        $listener = new EntityValidationListener();

        $this->assertInstanceOf(AggregateListenerInterface::class, $listener);
    }

    /**
     * Assert that the listener will register the correct event listeners in addListeners() method
     *
     * @throws EventListenerException
     */
    public function testAddListeners(): void
    {
        $listener = new EntityValidationListener();

        /** @var AddListenerAwareInterface&MockObject $collection */
        $collection = $this->createMock(AddListenerAwareInterface::class);

        $collection->expects($this->exactly(3))
            ->method('addListenerForEvent')
            ->withConsecutive(
                [EntityEventName::CREATE, [$listener, 'validateEntity'], 1000],
                [EntityEventName::UPDATE, [$listener, 'validateEntity'], 1000],
                [EntityEventName::DELETE, [$listener, 'validateEntity'], 1000]
            );

        $listener->addListeners($collection);
    }

    /**
     * Assert that if the entity event contains a NULL entity reference we will throw and log
     * a InvalidArgumentException
     *
     * @throws InvalidArgumentException
     */
    public function testValidateEntityWillThrowInvalidArgumentExceptionAndLogTheErrorIfTheEntityIsNull(): void
    {
        $listener = new EntityValidationListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $eventName = EntityEventName::UPDATE;
        $entityName = EntityInterface::class;

        $entity = null;

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $errorMessage = sprintf(
            'The required \'%s\' entity instance was not set for event \'%s\'',
            $entityName,
            $eventName
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);

        $listener->validateEntity($event);
    }

    /**
     * Assert that a InvalidArgumentException will be thrown if the entity instance, provided by the event is
     * of an invalid type
     *
     * @throws InvalidArgumentException
     */
    public function testValidateEntityWillThrowInvalidArgumentExceptionAndLogTheErrorIfTheEntityIsInvalid(): void
    {
        $listener = new EntityValidationListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $eventName = EntityEventName::UPDATE;

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);
        $entityName = 'Foo';

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $errorMessage = sprintf(
            'The entity class of type \'%s\' does not match the expected \'%s\' for event \'%s\'',
            get_class($entity),
            $entityName,
            $eventName
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($errorMessage);

        $listener->validateEntity($event);
    }

    /**
     * Assert that the validateEntity() method will log and exit without errors with a valid entity instance
     *
     * @throws InvalidArgumentException
     */
    public function testValidateEntityIsSuccessful(): void
    {
        $listener = new EntityValidationListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $eventName = EntityEventName::UPDATE;

        /** @var EntityInterface $entity */
        $entity = $this->createMock(EntityInterface::class);
        $entityName = get_class($entity);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $message = sprintf(
            'Successfully completed validation of \'%s\' for event \'%s\'',
            $entityName,
            $eventName
        );

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($message);

        $listener->validateEntity($event);
    }
}
