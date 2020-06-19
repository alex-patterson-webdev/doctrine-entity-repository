<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactory;
use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DoctrineEntityRepository\Constant\DateUpdateMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener;
use Arp\Entity\DateUpdatedAwareInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateUpdatedListenerTest extends TestCase
{
    /**
     * @var DateTimeFactory|MockObject
     */
    private $dateTimeFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->dateTimeFactory = $this->getMockForAbstractClass(DateTimeFactoryInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the class is callable.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener
     */
    public function testIsCallable(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory, $this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if the entity provided resolved to NULL that we log and exit early from the event listener.
     *
     * @throws \Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener::__invoke
     */
    public function testNullEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entity = null;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Ignoring update date time for entity \'%s\': The entity does not implement \'%s\'',
                    $entityName,
                    DateUpdatedAwareInterface::class
                )
            );

        $listener($event);
    }

    /**
     * Assert that if the entity provided resolved to and object that does NOT implement DateUpdatedAwareInterface that
     * the listener will log and return early.
     *
     * @throws \Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener::__invoke
     */
    public function testNonDateTimeAwareEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Ignoring update date time for entity \'%s\': The entity does not implement \'%s\'',
                    $entityName,
                    DateUpdatedAwareInterface::class
                )
            );

        $listener($event);
    }

    public function testDateTimeModeSetToDisabledWillLogAndPreventDateUpdate(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        /** @var DateUpdatedAwareInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(DateUpdatedAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_UPDATED_MODE, DateUpdateMode::ENABLED)
            ->willReturn(DateUpdateMode::DISABLED); // Will cause use to exit early

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'The date time update of field \'dateUpdated\' '
                    . 'has been disabled for entity \'%s::%s\' using configuration option \'%s\'',
                    $entityName,
                    $entityId,
                    EntityEventOption::DATE_UPDATED_MODE
                )
            );

        $listener($event);
    }

}
