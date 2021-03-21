<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\PersistServiceOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistService;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\PersistService
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence
 */
final class PersistServiceTest extends TestCase
{
    /**
     * @var string
     */
    private string $entityName;

    /**
     * @var EntityManagerInterface&MockObject
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface&MockObject
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

    /**
     * Assert that getEntityName() will return the fully qualified class name of the managed entity.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::getEntityName
     */
    public function testGetEntityNameWillReturnFQCNOfEntity(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->assertSame($this->entityName, $persistService->getEntityName());
    }

    /**
     * If an exception is raised when calling save(), and our entity has an ID value, then the
     * EntityEventName::UPDATE_ERROR event should be triggered and a new exception thrown.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::save
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::update
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::createEventException
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::createErrorEvent
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::dispatchEvent
     *
     * @throws PersistenceException
     */
    public function testSaveExceptionWillBeCaughtLoggedAndTheDispatchErrorEventTriggeredWhenEntityIdIsNotNull(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(true);

        $exceptionMessage = 'Test exception message for save() and update()';
        $exceptionCode = 123;
        $exception = new \Error($exceptionMessage, $exceptionCode);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(EntityEvent::class)],
                [$this->isInstanceOf(EntityErrorEvent::class)],
            )->willReturnOnConsecutiveCalls(
                $this->throwException($exception),
                $this->returnArgument(0)
            );

        $errorMessage = sprintf(
            'The persistence operation for entity \'%s\' failed: %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, $this->arrayHasKey('exception'));

        $persistService->save($entity);
    }

    /**
     * Assert that if we pass an entity with an id to save() that the entity will be updated and returned.
     *
     * @param array<mixed> $options Optional save options that should be passed to the updated method.
     *
     * @dataProvider getSaveWillUpdateAndReturnEntityWithIdData
     *
     * @covers       \Arp\DoctrineEntityRepository\Persistence\PersistService::save
     * @covers       \Arp\DoctrineEntityRepository\Persistence\PersistService::update
     * @covers       \Arp\DoctrineEntityRepository\Persistence\PersistService::dispatchEvent
     *
     * @throws PersistenceException
     */
    public function testSaveWillUpdateAndReturnEntityWithId(array $options = []): void
    {
        /** @var PersistService&MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs(
                [
                    $this->entityName,
                    $this->entityManager,
                    $this->eventDispatcher,
                    $this->logger,
                ]
            )->onlyMethods(['createEvent'])
            ->getMock();

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(true);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $persistService->expects($this->once())
            ->method('createEvent')
            ->with(EntityEventName::UPDATE, $entity, $options)
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturn($event);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->assertSame($entity, $persistService->save($entity, $options));
    }

    /**
     * @return array<mixed>
     */
    public function getSaveWillUpdateAndReturnEntityWithIdData(): array
    {
        return [
            [
                [
                    // empty options
                ],
                [
                    PersistServiceOption::FLUSH => true,
                ],
                [
                    PersistServiceOption::FLUSH => false,
                ],
            ],
        ];
    }

    /**
     * If an exception is raised when calling save(), and our entity does NOT have an ID value, then the
     * EntityEventName::CREATE_ERROR event should be triggered and a new exception thrown.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::save
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::insert
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::createEventException
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::createErrorEvent
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::dispatchEvent
     *
     * @throws PersistenceException
     */
    public function testSaveExceptionWillBeCaughtLoggedAndTheDispatchErrorEventTriggeredWhenEntityIdIsNull(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(false);

        $exceptionMessage = 'Test exception message for save() and insert()';
        $exceptionCode = 456;
        $exception = new \Error($exceptionMessage, $exceptionCode);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(EntityEvent::class)],
                [$this->isInstanceOf(EntityErrorEvent::class)],
            )->willReturnOnConsecutiveCalls(
                $this->throwException($exception),
                $this->returnArgument(0)
            );

        $errorMessage = sprintf(
            'The persistence operation for entity \'%s\' failed: %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, $this->arrayHasKey('exception'));

        $persistService->save($entity);
    }

    /**
     * Assert that an entity provided to save() that does not have an identity will be proxies to insert().
     *
     * @param array<mixed> $options
     *
     * @covers       \Arp\DoctrineEntityRepository\Persistence\PersistService::save
     * @covers       \Arp\DoctrineEntityRepository\Persistence\PersistService::insert
     * @covers       \Arp\DoctrineEntityRepository\Persistence\PersistService::dispatchEvent
     *
     * @dataProvider getSaveWillInsertAndReturnEntityWithNoIdData
     *
     * @throws PersistenceException
     */
    public function testSaveWillInsertAndReturnEntityWithNoId(array $options = []): void
    {
        /** @var PersistService&MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs(
                [
                    $this->entityName,
                    $this->entityManager,
                    $this->eventDispatcher,
                    $this->logger,
                ]
            )->onlyMethods(['createEvent'])
            ->getMock();

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(false);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $persistService->expects($this->once())
            ->method('createEvent')
            ->with(EntityEventName::CREATE, $entity, $options)
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturn($event);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->assertSame($entity, $persistService->save($entity, $options));
    }

    /**
     * @return array<mixed>
     */
    public function getSaveWillInsertAndReturnEntityWithNoIdData(): array
    {
        return [
            [

            ],
        ];
    }

    /**
     * Assert that if an exception occurs during the dispatch of the delete event, the delete_error event will be
     * dispatched with the entity error event containing the caught exception
     *
     * @throws PersistenceException
     */
    public function testDeleteWillTriggerDeleteErrorEventOnError(): void
    {
        $entityName = EntityInterface::class;

        /** @var PersistService&MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs([
                $entityName,
                $this->entityManager,
                $this->eventDispatcher,
                $this->logger,
            ])->onlyMethods(['createEvent', 'createErrorEvent'])
            ->getMock();

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);
        $options = [];

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $persistService->expects($this->once())
            ->method('createEvent')
            ->with(EntityEventName::DELETE, $entity, $options)
            ->willReturn($event);

        $exceptionMessage = 'This is a test exception message for ' . __FUNCTION__;
        $exceptionCode = 123;
        $exception = new EventListenerException($exceptionMessage, $exceptionCode);

        /** @var EntityErrorEvent&MockObject $errorEvent */
        $errorEvent = $this->createMock(EntityErrorEvent::class);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive([$event], [$errorEvent])
            ->willReturnOnConsecutiveCalls(
                $this->throwException($exception),
                $errorEvent
            );

        $persistService->expects($this->once())
            ->method('createErrorEvent')
            ->with(EntityEventName::DELETE_ERROR, $exception)
            ->willReturn($errorEvent);

        $errorEvent->expects($this->once())
            ->method('getException')
            ->willReturn($exception);

        $errorMessage = sprintf(
            'The persistence operation for entity \'%s\' failed: %s',
            $entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, $this->arrayHasKey('exception'));

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The persistence operation for entity \'%s\' failed: %s',
                $this->entityName,
                $exceptionMessage
            )
        );

        $persistService->delete($entity, $options);
    }

    /**
     * Assert that the delete event is dispatched when
     *
     * @throws PersistenceException
     */
    public function testDeleteWillDispatchDeleteEventAndReturnTrue(): void
    {
        $entityName = EntityInterface::class;

        /** @var PersistService&MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs([
                $entityName,
                $this->entityManager,
                $this->eventDispatcher,
                $this->logger,
            ])->onlyMethods(['createEvent'])
            ->getMock();

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);
        $options = [];

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $persistService->expects($this->once())
            ->method('createEvent')
            ->with(EntityEventName::DELETE, $entity, $options)
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturn($event);

        $this->assertTrue($persistService->delete($entity, $options));
    }

    /**
     * Assert that a PersistenceException will be thrown by persist() if the provided entity instance is not an
     * instance of the mapped entity class.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::persist
     */
    public function testPersistWillThrowPersistenceExceptionIfProvidedEntityIsAnInvalidType(): void
    {
        $entityName = \stdClass::class;

        $persistService = new PersistService(
            $entityName, // Invalid entity class name...
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $errorMessage = sprintf(
            'The \'entity\' argument must be an object of type \'%s\'; \'%s\' provided in \'%s::%s\'',
            $entityName,
            get_class($entity),
            PersistService::class,
            'persist'
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $persistService->persist($entity);
    }

    /**
     * Assert that if the $entity provided to persist() cannot be persisted any raised exception is caught, logged
     * and then rethrown as a PersistenceException.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::persist
     */
    public function testPersistWillThrowPersistenceExceptionIfTheEntityCannotBePersisted(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $exceptionMessage = 'This is a test error message for persist()';
        $exceptionCode = 456;
        $exception = new \Error($exceptionMessage, $exceptionCode);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity)
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'The persist operation failed for entity \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception]);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $persistService->persist($entity);
    }

    /**
     * Assert that persist() will successfully proxy the provided $entity to the entity manager persist().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::persist
     *
     * @throws PersistenceException
     */
    public function testPersist(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $persistService->persist($entity);
    }

    /**
     * Assert that exception raised from flush() are logged and rethrown as PersistenceException.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::flush
     *
     * @throws PersistenceException
     */
    public function testFlushExceptionsAreLoggedAndRethrownAsPersistenceException(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $exceptionMessage = 'This is a test error message for flush()';
        $exceptionCode = 999;
        $exception = new \Error($exceptionMessage, $exceptionCode);

        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'The flush operation failed for entity \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception, 'entity_name' => $this->entityName]);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $persistService->flush();
    }

    /**
     * Assert that flush() will call the internal entity manager flush().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::flush
     *
     * @throws PersistenceException
     */
    public function testFlush(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->entityManager->expects($this->once())->method('flush');

        $persistService->flush();
    }

    /**
     * Assert that exceptions raised in clear() are logged and rethrown as PersistenceException.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::clear
     *
     * @throw  PersistenceException
     */
    public function testClearExceptionsAreLoggedAndRethrownAsPersistenceException(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $exceptionMessage = 'This is a test exception message for clear()';
        $exceptionCode = 888;
        $exception = new \Error($exceptionMessage, $exceptionCode);

        $this->entityManager->expects($this->once())
            ->method('clear')
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'The clear operation failed for entity \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception, 'entity_name' => $this->entityName]);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $persistService->clear();
    }

    /**
     * Assert that calls to clear() will proxy to the internal entity manager clear().
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::clear
     *
     * @throws PersistenceException
     */
    public function testClear(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->entityManager->expects($this->once())->method('clear');

        $persistService->clear();
    }
}
