<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Constant\DateCreateMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DateCreatedAwareInterface;
use Arp\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateCreatedListenerTest extends TestCase
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
        $listener = new DateCreatedListener($this->dateTimeFactory);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if the entity provided resolved to NULL that we log and exit early from the event listener
     *
     * @throws PersistenceException
     */
    public function testNullEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory);

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
                    'Ignoring the date time update for entity \'%s\': The entity does not implement \'%s\'',
                    $entityName,
                    DateCreatedAwareInterface::class
                )
            );

        $listener($event);
    }

    /**
     * Assert that if the entity provided resolved to and object that does NOT implement DateCreatedListener that
     * the listener will log and return early
     *
     * @throws PersistenceException
     */
    public function testNonDateTimeAwareEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory);

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
                    'Ignoring the date time update for entity \'%s\': The entity does not implement \'%s\'',
                    $entityName,
                    DateCreatedAwareInterface::class
                )
            );

        $listener($event);
    }

    /**
     * Assert that we can enabled/disable the date update if the configuration options include a DATE_CREATED_MODE of
     * ENABLED/DISABLED
     *
     * @throws PersistenceException
     */
    public function testDateTimeModeSetToDisabledWillLogAndPreventDateUpdate(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        /** @var DateCreatedAwareInterface&MockObject $entity */
        $entity = $this->createMock(DateCreatedAwareInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_CREATED_MODE, DateCreateMode::ENABLED)
            ->willReturn(DateCreateMode::DISABLED); // Will cause use to exit early

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'The date time update of field \'dateCreated\' '
                    . 'has been disabled for new entity \'%s\' using configuration option \'%s\'',
                    $entityName,
                    EntityEventOption::DATE_CREATED_MODE
                )
            );

        $listener($event);
    }

    /**
     * Assert that DateTimeFactoryException's are caught and rethrown as PersistenceException in __invoke()

     * @throws PersistenceException
     */
    public function testDateTimeFactoryFailureWillBeLoggedAndRethrownAsAPersistenceException(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        /** @var DateCreatedAwareInterface&MockObject $entity */
        $entity = $this->createMock(DateCreatedAwareInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_CREATED_MODE, DateCreateMode::ENABLED)
            ->willReturn(DateCreateMode::ENABLED);

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
            ->with($errorMessage, ['entity_name' => $entityName, 'exception' => $exception]);

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
        $listener = new DateCreatedListener($this->dateTimeFactory);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        /** @var DateCreatedAwareInterface&MockObject $entity */
        $entity = $this->createMock(DateCreatedAwareInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_CREATED_MODE, DateCreateMode::ENABLED)
            ->willReturn(DateCreateMode::ENABLED);

        $dateUpdated = new \DateTime();

        $this->dateTimeFactory->expects($this->once())
            ->method('createDateTime')
            ->willReturn($dateUpdated);

        $message = sprintf(
            'The \'dateCreated\' property for entity \'%s\' has been updated with new date time \'%s\'',
            $entityName,
            $dateUpdated->format(\DateTime::ATOM)
        );

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($message);

        $listener($event);
    }
}
