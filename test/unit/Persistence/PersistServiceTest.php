<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\CollectionEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistService;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\Entity\EntityTrait;
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

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the class implements PersistServiceInterface
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
     * Assert that getEntityName() will return the fully qualified class name of the managed entity
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
     * @throws PersistenceException
     */
    public function testSaveExceptionWillBeCaughtLoggedAndZeroReturnedIfTheEntityIdIsNotNull(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(true);

        $exceptionMessage = 'Test exception message for save() and update()';
        $exceptionCode = 123;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(EntityEvent::class)],
                [$this->isInstanceOf(EntityErrorEvent::class)],
            )->willReturnOnConsecutiveCalls(
                $this->throwException($exception),
                $this->returnArgument(0)
            );

        $this->assertSame($entity, $persistService->save($entity));
    }

    /**
     * Assert that if we pass an entity with an id to save() that the entity will be updated and returned.
     *
     * @param array<mixed> $options Optional save options that should be passed to the updated method.
     *
     * @dataProvider getSaveWillUpdateAndReturnEntityWithIdData
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
        $entity = $this->createMock(EntityInterface::class);

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
            ],
        ];
    }

    /**
     * If an exception is raised when calling save(), and our entity does NOT have an ID value, then the
     * EntityEventName::CREATE_ERROR event should be triggered and a new exception thrown
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
        $entity = $this->createMock(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(false);

        $exceptionMessage = 'Test exception message for ' . __FUNCTION__;
        $exceptionCode = 456;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(EntityEvent::class)],
                [$this->isInstanceOf(EntityErrorEvent::class)],
            )->willReturnOnConsecutiveCalls(
                $this->throwException($exception),
                $this->returnArgument(0)
            );

        $this->assertSame($entity, $persistService->save($entity));
    }

    /**
     * Assert that an entity provided to save() that does not have an identity will be proxies to insert()
     *
     * @param array<mixed> $options
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
        $entity = $this->createMock(EntityInterface::class);

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
        $exception = new \Exception($exceptionMessage, $exceptionCode);

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

        $this->assertFalse($persistService->delete($entity, $options));
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
     * Assert that calls to deleteCollection() will dispatch the EntityEventName::DELETE_COLLECTION event using
     * the provided $collection
     *
     * @throws PersistenceException
     */
    public function testDeleteCollectionWillDispatchDeleteCollectionEvent(): void
    {
        $entityName = EntityInterface::class;

        /** @var PersistService&MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs([
                $entityName,
                $this->entityManager,
                $this->eventDispatcher,
                $this->logger,
            ])->onlyMethods(['createCollectionEvent'])
            ->getMock();

        /** @var array<EntityInterface&MockObject> $collection */
        $collection = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        $options = [
            EntityEventOption::LOG_ERRORS       => true,
            EntityEventOption::THROW_EXCEPTIONS => true,
            EntityEventOption::TRANSACTION_MODE => false,
        ];

        /** @var CollectionEvent&MockObject $event */
        $event = $this->createMock(CollectionEvent::class);

        $persistService->expects($this->once())
            ->method('createCollectionEvent')
            ->with(EntityEventName::DELETE_COLLECTION, $collection, $options)
            ->willReturn($event);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturn($event);

        $event->expects($this->once())
            ->method('getParam')
            ->with('deleted_count', 0)
            ->willReturn(count($collection));

        $this->assertSame(count($collection), $persistService->deleteCollection($collection, $options));
    }

    /**
     * Assert that calls to deleteCollection() will dispatch the EntityEventName::DELETE_COLLECTION_ERROR
     * if unable to delete the provided $collection
     *
     * @throws PersistenceException
     */
    public function testDeleteCollectionWillDispatchADeleteCollectionErrorEvent(): void
    {
        $entityName = EntityInterface::class;

        /** @var PersistService&MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs([
                $entityName,
                $this->entityManager,
                $this->eventDispatcher,
                $this->logger,
            ])->onlyMethods(['createCollectionEvent', 'createErrorEvent'])
            ->getMock();

        /** @var array<EntityInterface&MockObject> $collection */
        $collection = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        $options = [
            EntityEventOption::LOG_ERRORS       => true,
            EntityEventOption::THROW_EXCEPTIONS => true,
            EntityEventOption::TRANSACTION_MODE => false,
        ];

        /** @var CollectionEvent&MockObject $event */
        $event = $this->createMock(CollectionEvent::class);

        $persistService->expects($this->once())
            ->method('createCollectionEvent')
            ->with(EntityEventName::DELETE_COLLECTION, $collection, $options)
            ->willReturn($event);

        $exceptionMessage = 'This is a test exception message for ' . __FUNCTION__;
        $exceptionCode = 999;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        /** @var EntityErrorEvent&MockObject $errorEvent */
        $errorEvent = $this->createMock(EntityErrorEvent::class);

        $persistService->expects($this->once())
            ->method('createErrorEvent')
            ->with(EntityEventName::DELETE_COLLECTION_ERROR, $exception)
            ->willReturn($errorEvent);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive([$event], [$errorEvent])
            ->willReturnOnConsecutiveCalls(
                $this->throwException($exception),
                $errorEvent
            );

        $event->expects($this->once())
            ->method('getParam')
            ->with('deleted_count', 0)
            ->willReturn(0);

        $this->assertSame(0, $persistService->deleteCollection($collection, $options));
    }

    /**
     * Assert that a PersistenceException will be thrown by persist() if the provided entity instance is not an
     * instance of the mapped entity class
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
        $entity = $this->createMock(EntityInterface::class);

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
     * and then rethrown as a PersistenceException
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
        $entity = $this->createMock(EntityInterface::class);

        $exceptionMessage = 'This is a test error message for persist()';
        $exceptionCode = 456;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

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
     * Assert that persist() will successfully proxy the provided $entity to the entity manager persist()
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
        $entity = $this->createMock(EntityInterface::class);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $persistService->persist($entity);
    }

    /**
     * Assert that exception raised from flush() are logged and rethrown as PersistenceException
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
        $exception = new \Exception($exceptionMessage, $exceptionCode);

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
     * Assert that flush() will call the internal entity manager flush()
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
     * Assert that exceptions raised in clear() are logged and rethrown as PersistenceException
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
        $exception = new \Exception($exceptionMessage, $exceptionCode);

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
     * Assert that calls to clear() will proxy to the internal entity manager clear()
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

    /**
     * Assert a PersistenceException is thrown from refresh() if the provided Entity is not managed by this
     * repository class
     *
     * @throws PersistenceException
     */
    public function testRefreshWillThrowPersistenceExceptionForInvalidEntity(): void
    {
        $entity = new class() implements EntityInterface {
            use EntityTrait;
        };

        $entity2 = new class() implements EntityInterface {
            use EntityTrait;
        };

        $this->entityName = get_class($entity2);

        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->entityManager->expects($this->never())
            ->method('refresh')
            ->with($entity);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The \'entity\' argument must be an object of type \'%s\'; \'%s\' provided in \'%s\'',
                $this->entityName,
                get_class($entity),
                PersistService::class . '::refresh'
            )
        );

        $persistService->refresh($entity);
    }

    /**
     * Assert a PersistenceException is thrown from refresh() if the refresh() action fails
     *
     * @throws PersistenceException
     */
    public function testRefreshError(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $exceptionMessage = 'This is a test exception message for ' . __FUNCTION__;
        $exceptionCode = 987;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $this->entityManager->expects($this->once())
            ->method('refresh')
            ->with($entity)
            ->willThrowException($exception);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionCode($exceptionCode);
        $this->expectExceptionMessage(
            sprintf(
                'The refresh operation failed for entity \'%s\' : %s',
                $this->entityName,
                $exceptionMessage
            )
        );

        $persistService->refresh($entity);
    }

    /**
     * Assert calls to refresh() will result in the $entity being passed to EntityManager::refresh()
     *
     * @throws PersistenceException
     */
    public function testRefresh(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $this->entityManager->expects($this->once())
            ->method('refresh')
            ->with($entity);

        $persistService->refresh($entity);
    }

    /**
     * Assert calls to beginTransaction() will proxy to the managed EntityManager instance
     *
     * @throws PersistenceException
     */
    public function testBeginTransaction(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->entityManager->expects($this->once())->method('beginTransaction');

        $persistService->beginTransaction();
    }

    /**
     * Assert calls to beginTransaction() will throw a PersistenceException on error
     *
     * @throws PersistenceException
     */
    public function testBeginTransactionError(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $exceptionMessage = 'This is a test exception message for ' . __FUNCTION__;
        $exceptionCode = 123;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction')
            ->willThrowException($exception);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage(sprintf('Failed to start transaction : %s', $exceptionMessage));
        $this->expectExceptionCode($exceptionCode);

        $persistService->beginTransaction();
    }

    /**
     * Assert calls to commitTransaction() will proxy to the managed EntityManager instance
     *
     * @throws PersistenceException
     */
    public function testCommitTransaction(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->entityManager->expects($this->once())->method('commit');

        $persistService->commitTransaction();
    }

    /**
     * Assert calls to commitTransaction() will throw a PersistenceException on error
     *
     * @throws PersistenceException
     */
    public function testCommitTransactionError(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $exceptionMessage = 'This is a test exception message for ' . __FUNCTION__;
        $exceptionCode = 123;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $this->entityManager->expects($this->once())
            ->method('commit')
            ->willThrowException($exception);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage(sprintf('Failed to commit transaction : %s', $exceptionMessage));
        $this->expectExceptionCode($exceptionCode);

        $persistService->commitTransaction();
    }

    /**
     * Assert calls to rollbackTransaction() will proxy to the managed EntityManager instance
     *
     * @throws PersistenceException
     */
    public function testRollbackTransaction(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $this->entityManager->expects($this->once())->method('rollback');

        $persistService->rollbackTransaction();
    }

    /**
     * Assert calls to rollbackTransaction() will throw a PersistenceException on error
     *
     * @throws PersistenceException
     */
    public function testRollbackTransactionError(): void
    {
        $persistService = new PersistService(
            $this->entityName,
            $this->entityManager,
            $this->eventDispatcher,
            $this->logger
        );

        $exceptionMessage = 'This is a test exception message for ' . __FUNCTION__;
        $exceptionCode = 123;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $this->entityManager->expects($this->once())
            ->method('rollback')
            ->willThrowException($exception);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage(sprintf('Failed to rollback transaction : %s', $exceptionMessage));
        $this->expectExceptionCode($exceptionCode);

        $persistService->rollbackTransaction();
    }
}
