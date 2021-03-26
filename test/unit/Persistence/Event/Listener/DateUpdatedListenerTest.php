<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Constant\DateUpdateMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DateUpdatedAwareInterface;
use Arp\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateUpdatedListenerTest extends TestCase
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
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->dateTimeFactory = $this->createMock(DateTimeFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the class is callable
     */
    public function testIsCallable(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if the entity provided resolved to NULL that we log and exit early from the event listener.
     *
     * @throws PersistenceException
     */
    public function testNullEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entity = null;

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

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
     * @throws PersistenceException
     */
    public function testNonDateTimeAwareEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

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
     * Assert that we can enabled/disable the date update if the configuration options include a DATE_UPDATED_MODE of
     * ENABLED/DISABLED
     *
     * @throws PersistenceException
     */
    public function testDateTimeModeSetToDisabledWillLogAndPreventDateUpdate(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        /** @var DateUpdatedAwareInterface&MockObject $entity */
        $entity = $this->createMock(DateUpdatedAwareInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_UPDATED_MODE, DateUpdateMode::ENABLED)
            ->willReturn(DateUpdateMode::DISABLED); // Will cause use to exit early

        $this->logger->expects($this->once())
            ->method('debug')
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

    /**
     * Assert that DateTimeFactoryException's are caught and rethrown as PersistenceException in __invoke()
     *
     * @throws PersistenceException
     */
    public function testDateTimeFactoryFailureWillBeLoggedAndRethrownAsAPersistenceException(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        /** @var DateUpdatedAwareInterface&MockObject $entity */
        $entity = $this->createMock(DateUpdatedAwareInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_UPDATED_MODE, DateUpdateMode::ENABLED)
            ->willReturn(DateUpdateMode::ENABLED);

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
     * Assert that a valid DateUpdatedAwareInterface instance, with update mode enabled, will correctly set a new
     * \DateTime instance using setDateUpdated() method
     *
     * @throws PersistenceException
     */
    public function testDateUpdatedSuccess(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        /** @var DateUpdatedAwareInterface&MockObject $entity */
        $entity = $this->createMock(DateUpdatedAwareInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_UPDATED_MODE, DateUpdateMode::ENABLED)
            ->willReturn(DateUpdateMode::ENABLED);

        $dateUpdated = new \DateTime();

        $this->dateTimeFactory->expects($this->once())
            ->method('createDateTime')
            ->willReturn($dateUpdated);

        $message = sprintf(
            'The \'dateUpdated\' property for entity \'%s::%s\' has been updated with new date time \'%s\'',
            $entityName,
            $entityId,
            $dateUpdated->format(\DateTime::ATOM)
        );

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($message);

        $listener($event);
    }
}
