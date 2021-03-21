<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Constant\DateDeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DateDeletedAwareInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateDeletedListenerTest extends TestCase
{
    /**
     * @var DateTimeFactoryInterface&MockObject
     */
    private $dateTimeFactory;

    /**
     * @var LoggerInterface&MockObject
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
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::__construct
     */
    public function testIsCallable(): void
    {
        $listener = new DateDeletedListener($this->dateTimeFactory, $this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if the entity provided resolved to NULL that we log and exit early from the event listener.
     *
     * @throws PersistenceException
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::__invoke
     */
    public function testNullEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateDeletedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent&MockObject $event */
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
                    DateDeletedAwareInterface::class
                )
            );

        $listener($event);
    }

    /**
     * Assert that if the entity provided resolved to and object that does NOT implement DateDeletedAwareInterface that
     * the listener will log and return early.
     *
     * @throws PersistenceException
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::__invoke
     */
    public function testNonDateTimeAwareEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateDeletedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        /** @var EntityInterface&MockObject $entity */
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
                    DateDeletedAwareInterface::class
                )
            );

        $listener($event);
    }

    /**
     * Assert that we can enabled/disable the date update if the configuration options include a DATE_UPDATED_MODE of
     * ENABLED/DISABLED.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::__invoke
     *
     * @throws PersistenceException
     */
    public function testDateTimeModeSetToDisabledWillLogAndPreventDateUpdate(): void
    {
        $listener = new DateDeletedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        /** @var DateDeletedAwareInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(DateDeletedAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_DELETED_MODE, DateDeleteMode::ENABLED)
            ->willReturn(DateDeleteMode::DISABLED); // Will cause use to exit early

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'The date time update of field \'dateDeleted\' '
                    . 'has been disabled for entity \'%s::%s\' using configuration option \'%s\'',
                    $entityName,
                    $entityId,
                    EntityEventOption::DATE_DELETED_MODE
                )
            );

        $listener($event);
    }

    /**
     * Assert that DateTimeFactoryException's are caught and rethrown as PersistenceException in __invoke().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::__invoke
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::createDateTime
     *
     * @throws PersistenceException
     */
    public function testDateTimeFactoryFailureWillBeLoggedAndRethrownAsAPersistenceException(): void
    {
        $listener = new DateDeletedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        /** @var DateDeletedAwareInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(DateDeletedAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_DELETED_MODE, DateDeleteMode::ENABLED)
            ->willReturn(DateDeleteMode::ENABLED);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $exceptionMessage = 'This is a test exception message from DateTimeFactory';
        $exception = new DateTimeFactoryException($exceptionMessage);

        $this->dateTimeFactory->expects($this->once())
            ->method('createDateTime')
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'Failed to create the update date time instance for entity \'%s\': %s',
            $entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $listener($event);
    }

    /**
     * Assert that a valid DateDeletedAwareInterface instance, with update mode enabled, will correctly set a new
     * \DateTime instance using setDateDeleted() method.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::__invoke
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener::createDateTime
     *
     * @throws PersistenceException
     */
    public function testDateUpdatedSuccess(): void
    {
        $listener = new DateDeletedListener($this->dateTimeFactory, $this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        /** @var DateDeletedAwareInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(DateDeletedAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_DELETED_MODE, DateDeleteMode::ENABLED)
            ->willReturn(DateDeleteMode::ENABLED);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $dateUpdated = new \DateTime();

        $this->dateTimeFactory->expects($this->once())
            ->method('createDateTime')
            ->willReturn($dateUpdated);

        $message = sprintf(
            'The \'dateDeleted\' property for entity \'%s::%s\' has been updated with new date time \'%s\'',
            $entityName,
            $entityId,
            $dateUpdated->format(\DateTime::ATOM)
        );

        $this->logger->expects($this->once())
            ->method('info')
            ->with($message);

        $listener($event);
    }
}
