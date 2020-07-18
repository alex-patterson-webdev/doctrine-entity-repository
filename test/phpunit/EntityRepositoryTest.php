<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Constant\QueryServiceOption;
use Arp\DoctrineEntityRepository\EntityRepository;
use Arp\DoctrineEntityRepository\EntityRepositoryInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\DoctrineEntityRepository\Query\QueryServiceInterface;
use Arp\Entity\EntityInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
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
     * @var QueryServiceInterface|MockObject
     */
    private $queryService;

    /**
     * @var PersistServiceInterface|MockObject
     */
    private $persistService;

    /**
     * @var LoggerInterface|MockObject
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
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::__construct
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
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::__construct
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
     * Assert that method getClassName() will return the FQCN of the managed entity.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::getClassName
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
     * a EntityRepositoryException.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::find
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

        $exceptionMessage = 'This is a test exception message for find()';
        $exception = new \Exception($exceptionMessage);

        $this->queryService->expects($this->once())
            ->method('findOneById')
            ->with($entityId)
            ->willThrowException($exception);

        $errorMessage = sprintf('Unable to find entity of type \'%s\': %s', $this->entityName, $exceptionMessage);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception, 'id' => $entityId]);

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);

        $repository->find($entityId);
    }

    /**
     * Assert that find() will return NULL if the entity cannot be found by it's $id.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::find
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
     * Assert that find() will the found entity by it's $id.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::find
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

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);
        $entityId = 'FOO123';

        $this->queryService->expects($this->once())
            ->method('findOneById')
            ->with($entityId)
            ->willReturn($entity);

        $this->assertSame($entity, $repository->find($entityId));
    }

    /**
     * Assert that if the findOneBy() will catch and rethrow exception messages as EntityRepositoryException.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::findOneBy
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

        $exceptionMessage = 'This is a test exception message for findOneById()';
        $exceptionCode = 456;

        $exception = new \Exception($exceptionMessage, $exceptionCode);

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
     * Assert that findOneBy() will return NULL if the provided criteria doesn't match any existing entities.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::findOneBy
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
     * Assert findOneBy() will return a single matched entity instance.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::findOneBy
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

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

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
        /** @var EntityRepository|MockObject $repository */
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

        /** @var EntityInterface[]|MockObject[] $entities */
        $entities = [
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class),
        ];

        $repository->expects($this->once())
            ->method('findBy')
            ->with([])
            ->willReturn($entities);

        $this->assertSame($entities, $repository->findAll());
    }

    /**
     * Assert that the findBy() method will catch \Throwable exceptions, log the exception data and rethrow as a
     * EntityRepositoryException.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::findBy
     *
     * @throws EntityRepositoryException
     */
    public function testFindByWillCatchAndRethrowEntityRepositoryExceptionOnFailure(): void
    {
        /** @var EntityRepository|MockObject $repository */
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        $criteria = [];
        $options = [];

        $exceptionMessage = 'This is a foo test exception';
        $exceptionCode = 456;
        $exception = new \Error($exceptionMessage, $exceptionCode);

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
     * @param array $data
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::findBy
     *
     * @dataProvider getFindByWithSearchArgumentsData
     *
     * @throws EntityRepositoryException
     */
    public function testFindByWillPassValidSearchArguments(array $data): void
    {
        /** @var EntityRepository|MockObject $repository */
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

        /** @var EntityInterface[]|MockObject[] $collection */
        $collection = [
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class),
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
     * @return array
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
                        'name' => 'test',
                        'hello' => 'foo',
                    ],
                ]
            ],

            [
                [
                    'limit' => 100,
                ]
            ],

            [
                [
                    'offset' => 10,
                ]
            ],

            [
                [
                    'order_by' => [
                        'name' => 'desc',
                        'foo' => 'asc',
                    ],
                ]
            ],

            [
                [
                    'criteria' => [
                        'name' => 'test',
                        'hello' => 'foo',
                        'foo' => 123
                    ],
                    'offset' => 7,
                    'limit' => 1000,
                    'order_by' => [
                        'hello' => 'asc',
                    ],
                ],
            ],
        ];
    }

    /**
     * Assert that errors during save that throw a \Throwable exception are caught, logged and rethrown as a
     * EntityRepositoryException.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::save
     *
     * @throws EntityRepositoryException
     */
    public function testSaveWillCatchThrowableAndRethrowEntityRepositoryException(): void
    {
        /** @var EntityRepository|MockObject $repository */
        $repository = new EntityRepository(
            $this->entityName,
            $this->queryService,
            $this->persistService,
            $this->logger
        );

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $exceptionMessage = 'This is a test exception message for save()';
        $exceptionCode = 999;
        $exception = new \Error($exceptionMessage, $exceptionCode);

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
     * Assert that calls to save() will result in the entity being passed to the persist service.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::save
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

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $this->persistService->expects($this->once())
            ->method('save')
            ->with($entity, $options)
            ->willReturn($entity);

        $this->assertSame($entity, $repository->save($entity, $options));
    }

    /**
     * Assert that calls to clear that fail will be caught and rethrown as EntityRepositoryException.
     *
     * @covers \Arp\DoctrineEntityRepository\EntityRepository::clear
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

        $this->expectException(EntityRepositoryException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $repository->clear();
    }
}
