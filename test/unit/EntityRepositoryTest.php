<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Constant\PersistServiceOption;
use Arp\DoctrineEntityRepository\Constant\QueryServiceOption;
use Arp\DoctrineEntityRepository\EntityRepository;
use Arp\DoctrineEntityRepository\EntityRepositoryInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\DoctrineEntityRepository\Query\Exception\QueryServiceException;
use Arp\DoctrineEntityRepository\Query\QueryServiceInterface;
use Arp\Entity\EntityInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\AbstractEntityRepository
 * @covers \Arp\DoctrineEntityRepository\EntityRepository
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository
 */
final class EntityRepositoryTest extends TestCase
{
    /**
     * @var string
     */
    private string $entityName;

    /**
     * @var QueryServiceInterface&MockObject
     */
    private $queryService;

    /**
     * @var PersistServiceInterface&MockObject
     */
    private $persistService;

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

        $this->queryService = $this->getMockForAbstractClass(QueryServiceInterface::class);

        $this->persistService = $this->getMockForAbstractClass(PersistServiceInterface::class);

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert the EntityRepository implements the EntityRepositoryInterface
     */
    public function testImplementsEntityRepositoryInterface(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $this->assertInstanceOf(EntityRepositoryInterface::class, $repository);
    }

    /**
     * Assert the EntityRepository implements the ObjectRepository
     */
    public function testImplementsObjectRepository(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $this->assertInstanceOf(ObjectRepository::class, $repository);
    }

    /**
     * Assert that method getClassName() will return the FQCN of the managed entity
     */
    public function testGetClassNameWillReturnTheFullyQualifiedEntityClassName(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $this->assertSame($this->entityName, $repository->getClassName());
    }

    /**
     * Assert that if find() cannot load/fails then any thrown exception/throwable will be caught and rethrown as
     * a EntityRepositoryException
     *
     * @throws EntityRepositoryException
     */
    public function testFindCatchAndRethrowExceptionsAsEntityRepositoryExceptionIfTheFindQueryFails(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $entityId = 'FOO123';

        $exceptionMessage = 'This is a test exception message for '  . __FUNCTION__;
        $exceptionCode = 123;
        $exception = new QueryServiceException($exceptionMessage, $exceptionCode);

        $this->queryService->expects($this->once())
            ->method('findOneById')
            ->with($entityId)
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'Unable to find entity of type \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception, 'id' => $entityId]);

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionCode($exceptionCode);
        $this->expectExceptionMessage($errorMessage);

        $repository->find($entityId);
    }

    /**
     * Assert that find() will return NULL if the entity cannot be found by it's $id
     *
     * @throws EntityRepositoryException
     */
    public function testFindWillReturnNullIfNotEntityCanBeFound(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $entityId = 'FOO123';

        $this->queryService->expects($this->once())
            ->method('findOneById')
            ->with($entityId)
            ->willReturn(null);

        $this->assertNull($repository->find($entityId));
    }

    /**
     * Assert that find() will the found entity by it's $id
     *
     * @throws EntityRepositoryException
     */
    public function testFindWillReturnTheMatchedEntity(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);
        $entityId = 'FOO123';

        $this->queryService->expects($this->once())
            ->method('findOneById')
            ->with($entityId)
            ->willReturn($entity);

        $this->assertSame($entity, $repository->find($entityId));
    }

    /**
     * Assert that if the findOneBy() will catch and rethrow exception messages as EntityRepositoryException
     *
     * @throws EntityRepositoryException
     */
    public function testFindOneByWillThrowEntityRepositoryExceptionIfTheQueryCannotBePerformed(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $criteria = [
            'name'  => 'Test',
            'hello' => 'World',
            'test'  => 123,
        ];

        $exceptionMessage = 'This is a test exception message for ' . __FUNCTION__;
        $exceptionCode = 456;

        $exception = new QueryServiceException($exceptionMessage, $exceptionCode);

        $this->queryService->expects($this->once())
            ->method('findOne')
            ->with($criteria)
            ->willThrowException($exception);

        $errorMessage = sprintf('Unable to find entity of type \'%s\': %s', $this->entityName, $exceptionMessage);

        $this->logger->error($errorMessage, compact('exception', 'criteria'));

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $repository->findOneBy($criteria);
    }

    /**
     * Assert that findOneBy() will return NULL if the provided criteria doesn't match any existing entities
     *
     * @throws EntityRepositoryException
     */
    public function testFindOneByWillReturnNullIfAMatchingEntityCannotBeFound(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $criteria = [
            'foo'  => 'bar',
            'test' => 789,
        ];

        $this->queryService->expects($this->once())
            ->method('findOne')
            ->with($criteria)
            ->willReturn(null);

        $this->assertNull($repository->findOneBy($criteria));
    }

    /**
     * Assert findOneBy() will return a single matched entity instance
     *
     * @throws EntityRepositoryException
     */
    public function testFindOneByWillReturnASingleMatchEntity(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $criteria = [
            'fred' => 'test',
            'bob'  => 123,
        ];

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $this->queryService->expects($this->once())
            ->method('findOne')
            ->with($criteria)
            ->willReturn($entity);

        $this->assertSame($entity, $repository->findOneBy($criteria));
    }

    /**
     * Assert that calls to findAll() will proxy to findBy() with an empty array.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::findAll
     *
     * @throws EntityRepositoryException
     */
    public function testFindAllWillProxyAnEmptyArrayToFindBy(): void
    {
        /** @var EntityRepository&MockObject $repository */
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->setConstructorArgs(
                [
                    $this->entityName,
                    $this->queryService,
                    $this->persistService,
                    $this->logger,
                ]
            )
            ->onlyMethods(['findBy'])
            ->getMock();

        /** @var EntityInterface[]&MockObject[] $entities */
        $entities = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        $repository->expects($this->once())
            ->method('findBy')
            ->with([])
            ->willReturn($entities);

        $this->assertSame($entities, $repository->findAll());
    }

    /**
     * Assert that the findBy() method will catch \Throwable exceptions, log the exception data and rethrow as a
     * EntityRepositoryException
     *
     * @throws EntityRepositoryException
     */
    public function testFindByWillCatchAndRethrowEntityRepositoryExceptionOnFailure(): void
    {
        /** @var EntityRepository&MockObject $repository */
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $criteria = [];
        $options = [];

        $exceptionMessage = 'This is a foo test exception for ' . __FUNCTION__;
        $exceptionCode = 456;
        $exception = new QueryServiceException($exceptionMessage, $exceptionCode);

        $this->queryService->expects($this->once())
            ->method('findMany')
            ->with($criteria, $options)
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'Unable to return a collection of type \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, compact('exception', 'criteria', 'options'));

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);

        $repository->findBy([]);
    }

    /**
     * Assert the required search arguments are passed to findMany().
     *
     * @param array<mixed> $data
     *
     * @covers       \Arp\DoctrineEntityRepository\EntityRepository::findBy
     *
     * @dataProvider getFindByWithSearchArgumentsData
     *
     * @throws EntityRepositoryException
     */
    public function testFindByWillPassValidSearchArguments(array $data): void
    {
        /** @var EntityRepository&MockObject $repository */
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $criteria = $data['criteria'] ?? [];
        $options = [];

        if (isset($data['order_by'])) {
            $options[QueryServiceOption::ORDER_BY] = $data['order_by'];
        }

        if (isset($data['limit'])) {
            $options[QueryServiceOption::LIMIT] = $data['limit'];
        }

        if (isset($data['offset'])) {
            $options[QueryServiceOption::OFFSET] = $data['offset'];
        }

        /** @var EntityInterface[]&MockObject[] $collection */
        $collection = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        $this->queryService->expects($this->once())
            ->method('findMany')
            ->with($criteria, $options)
            ->willReturn($collection);

        $result = $repository->findBy(
            $criteria,
            $data['order_by'] ?? null,
            $data['limit'] ?? null,
            $data['offset'] ?? null
        );

        $this->assertSame($collection, $result);
    }

    /**
     * @return array<mixed>
     */
    public function getFindByWithSearchArgumentsData(): array
    {
        return [
            [
                // Empty Data
                [

                ],
            ],

            [
                [
                    'criteria' => [
                        'name'  => 'test',
                        'hello' => 'foo',
                    ],
                ],
            ],

            [
                [
                    'limit' => 100,
                ],
            ],

            [
                [
                    'offset' => 10,
                ],
            ],

            [
                [
                    'order_by' => [
                        'name' => 'desc',
                        'foo'  => 'asc',
                    ],
                ],
            ],

            [
                [
                    'criteria' => [
                        'name'  => 'test',
                        'hello' => 'foo',
                        'foo'   => 123,
                    ],
                    'offset'   => 7,
                    'limit'    => 1000,
                    'order_by' => [
                        'hello' => 'asc',
                    ],
                ],
            ],
        ];
    }

    /**
     * Assert that errors during save that throw a \Throwable exception are caught, logged and rethrown as a
     * EntityRepositoryException
     *
     * @throws EntityRepositoryException
     */
    public function testSaveWillCatchThrowableAndRethrowEntityRepositoryException(): void
    {
        /** @var EntityRepository&MockObject $repository */
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $exceptionMessage = 'This is a test exception message for save()';
        $exceptionCode = 999;
        $exception = new PersistenceException($exceptionMessage, $exceptionCode);

        $errorMessage = sprintf('Unable to save entity of type \'%s\': %s', $this->entityName, $exceptionMessage);

        $this->persistService->expects($this->once())
            ->method('save')
            ->with($entity)
            ->willThrowException($exception);

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $this->assertSame($entity, $repository->save($entity));
    }

    /**
     * Assert that calls to save() will result in the entity being passed to the persist service
     *
     * @throws EntityRepositoryException
     */
    public function testSaveAndReturnEntityWithProvidedOptions(): void
    {
        $options = [
            'foo' => 'bar',
        ];

        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $this->persistService->expects($this->once())
            ->method('save')
            ->with($entity, $options)
            ->willReturn($entity);

        $this->assertSame($entity, $repository->save($entity, $options));
    }

    /**
     * Assert that if we provide an incorrect data type for $entity to delete() an EntityRepositoryException
     * will be thrown
     *
     * @param mixed $entity
     *
     * @dataProvider getDeleteWillThrowEntityRepositoryExceptionIfProvidedEntityIsInvalidData
     *
     * @throws EntityRepositoryException
     */
    public function testDeleteWillThrowEntityRepositoryExceptionIfProvidedEntityIsInvalid($entity): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $errorMessage = sprintf(
            'The \'entity\' argument must be a \'string\' or an object of type \'%s\'; '
            . '\'%s\' provided in \'%s::delete\'',
            EntityInterface::class,
            (is_object($entity) ? get_class($entity) : gettype($entity)),
            EntityRepository::class
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);

        $repository->delete($entity);
    }

    /**
     * @return array<mixed>
     */
    public function getDeleteWillThrowEntityRepositoryExceptionIfProvidedEntityIsInvalidData(): array
    {
        return [
            [true],
            [new \stdClass()],
            [45.67],
        ];
    }

    /**
     * Assert that a EntityRepositoryException will be thrown from delete if providing an entity id that does not
     * exist
     *
     * @throws EntityRepositoryException
     */
    public function testDeleteWillThrowEntityNotFoundExceptionIfEntityCannotBeFoundWithStringId(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $id = 'FOO123';

        $errorMessage = sprintf(
            'Unable to delete entity \'%s::%s\': The entity could not be found',
            $this->entityName,
            $id
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);

        $repository->delete($id);
    }

    /**
     * Assert that a EntityRepositoryException will be thrown if the call to delete() fails.
     *
     * @throws EntityRepositoryException
     */
    public function testDeleteWillThrowEntityRepositoryExceptionIfEntityCannotDeleted(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $options = [
            PersistServiceOption::FLUSH => true,
        ];

        $exceptionMessage = 'This is a test exception message';
        $exceptionCode = 789;
        $exception = new PersistenceException($exceptionMessage, $exceptionCode);

        $this->persistService->expects($this->once())
            ->method('delete')
            ->with($entity, $options)
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'Unable to delete entity of type \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception, 'entity_name' => $this->entityName]);

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);

        $repository->delete($entity, $options);
    }

    /**
     * Assert that we are able to delete an entity by it's id or instance
     *
     * @param EntityInterface|string $entity
     * @param array<mixed>           $options
     *
     * @dataProvider getEntityDeleteData
     * @throws EntityRepositoryException
     */
    public function testEntityDelete($entity, array $options = []): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        if (is_string($entity)) {
            /** @var EntityInterface&MockObject $entityObject */
            $entityObject = $this->createMock(EntityInterface::class);

            $this->queryService->expects($this->once())
                ->method('findOneById')
                ->with($entity)
                ->willReturn($entityObject);
        } else {
            $entityObject = $entity;
        }

        $this->persistService->expects($this->once())
            ->method('delete')
            ->with($entityObject, $options)
            ->willReturn(true);

        $this->assertTrue($repository->delete($entity, $options));
    }

    /**
     * @return array<mixed>
     */
    public function getEntityDeleteData(): array
    {
        return [
            [
                'Foo123',
                [
                    'foo'  => 'bar',
                    'test' => 'Hello',
                ],
            ],
            [
                $this->createMock(EntityInterface::class),
                [
                    'foo'  => 'bar',
                    'test' => 'Hello',
                ],
            ],
        ];
    }

    /**
     * Assert that calls to clear that fail will be caught and rethrown as EntityRepositoryException
     *
     * @throws EntityRepositoryException
     */
    public function testClearWillThrowEntityRepositoryExceptionOnFailure(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $exceptionCode = 123;
        $exceptionMessage = 'This is a test exception message';
        $exception = new PersistenceException($exceptionMessage, $exceptionCode);

        $this->persistService->expects($this->once())
            ->method('clear')
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'Unable to clear entity of type \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception, 'entity_name' => $this->entityName]);

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $repository->clear();
    }

    /**
     * Assert that calls to clear() will proxy to PersistService::clear()
     *
     * @throws EntityRepositoryException
     */
    public function testClearWillProxyToPersistServiceClear(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $this->persistService->expects($this->once())->method('clear');

        $repository->clear();
    }

    /**
     * Assert that calls to refresh that fail will be caught and rethrown as EntityRepositoryException
     *
     * @throws EntityRepositoryException
     */
    public function testRefreshWillThrowEntityRepositoryExceptionOnFailure(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $exceptionCode = 456;
        $exceptionMessage = 'This is a test exception message';
        $exception = new PersistenceException($exceptionMessage, $exceptionCode);

        $this->persistService->expects($this->once())
            ->method('refresh')
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'Unable to refresh entity of type \'%s\': %s',
            $this->entityName,
            $exceptionMessage
        );

        $id = 'HELLO123';
        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($id);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $errorMessage,
                ['exception' => $exception, 'entity_name' => $this->entityName, 'id' => $id]
            );

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $repository->refresh($entity);
    }

    /**
     * Assert that calls to refresh() will proxy to PersistService::refresh()
     *
     * @throws EntityRepositoryException
     */
    public function testRefreshWillProxyToPersistServiceClear(): void
    {
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $this->persistService->expects($this->once())
            ->method('refresh')
            ->with($entity);

        $repository->refresh($entity);
    }
}
