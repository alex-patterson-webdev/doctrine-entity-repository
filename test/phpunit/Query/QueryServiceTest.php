<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Query;

use Arp\DoctrineEntityRepository\Constant\QueryServiceOption;
use Arp\DoctrineEntityRepository\Query\Exception\QueryServiceException;
use Arp\DoctrineEntityRepository\Query\QueryService;
use Arp\DoctrineEntityRepository\Query\QueryServiceInterface;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Query
 */
final class QueryServiceTest extends TestCase
{
    /**
     * @var string
     */
    private string $entityName;

    /**
     * @var EntityManagerInterface|MockObject
     */
    private $entityManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Setup the test case dependencies.
     */
    public function setUp(): void
    {
        $this->entityName = EntityInterface::class;

        $this->entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the QueryService implements QueryServiceInterface
     *
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::__construct
     */
    public function testImplementsQueryServiceInterface(): void
    {
        $queryService = new QueryService($this->entityName, $this->entityManager, $this->logger);

        $this->assertInstanceOf(QueryServiceInterface::class, $queryService);
    }

    /**
     * Assert that a query builder is returned without an alias.
     *
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::createQueryBuilder
     */
    public function testCreateQueryBuilderWillReturnQueryBuilderWithoutAlias(): void
    {
        $queryService = new QueryService($this->entityName, $this->entityManager, $this->logger);

        /** @var QueryBuilder|MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->never())->method('select');
        $queryBuilder->expects($this->never())->method('from');

        $this->assertInstanceOf(QueryBuilder::class, $queryService->createQueryBuilder());
    }

    /**
     * Assert that a query builder is returned with the provided $alias
     *
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::createQueryBuilder
     */
    public function testCreateQueryBuilderWillReturnQueryBuilderWithAlias(): void
    {
        $queryService = new QueryService($this->entityName, $this->entityManager, $this->logger);

        $alias = 'foo';

        /** @var QueryBuilder|MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with($alias)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('from')
            ->with($this->entityName, $alias);

        $this->assertInstanceOf(QueryBuilder::class, $queryService->createQueryBuilder($alias));
    }

    /**
     * Assert that if we provide a invalid query object to execute() a QueryServiceException will be thrown.
     *
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::execute
     *
     * @throws QueryServiceException
     */
    public function testExecuteWillThrowQueryServiceExceptionIfProvidedInvalidQuery(): void
    {
        $queryService = new QueryService($this->entityName, $this->entityManager, $this->logger);

        $invalidQuery = new \stdClass();

        $this->expectException(QueryServiceException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Query provided must be of type \'%s\'; \'%s\' provided in \'%s::%s\'.',
                AbstractQuery::class,
                (is_object($invalidQuery) ? get_class($invalidQuery) : gettype($invalidQuery)),
                QueryService::class,
                'execute'
            )
        );

        $queryService->execute($invalidQuery);
    }

    /**
     * Assert that a query object provided to execute will be prepared with the provided options and then executed.
     *
     * @param array $options  The optional query options to assert get set on the query when being prepared.
     *
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::execute
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::prepareQuery
     *
     * @dataProvider getExecuteWillPrepareAndExecuteQueryData
     * @throws QueryServiceException
     */
    public function testExecuteWillPrepareAndExecuteQuery(array $options = []): void
    {
        $queryService = new QueryService($this->entityName, $this->entityManager, $this->logger);

        /** @var AbstractQuery|MockObject $query */
        $query = $this->createMock(AbstractQuery::class);

        if (array_key_exists('params', $options)) {
            $query->expects($this->once())
                ->method('setParameters')
                ->with($options['params']);
        }

        if (array_key_exists(QueryServiceOption::HYDRATION_MODE, $options)) {
            $query->expects($this->once())
                ->method('setHydrationMode')
                ->with($options[QueryServiceOption::HYDRATION_MODE]);
        }

        if (array_key_exists('hydration_cache_profile', $options)) {
            $query->expects($this->once())
                ->method('setHydrationCacheProfile')
                ->with($options['hydration_cache_profile']);
        }

        if (array_key_exists('result_set_mapping', $options)) {
            $query->expects($this->once())
                ->method('setResultSetMapping')
                ->with($options['result_set_mapping']);
        }

        if (!empty($options[QueryServiceOption::DQL]) && $query instanceof Query) {
            $query->expects($this->once())
                ->method('setDQL')
                ->with($options[QueryServiceOption::DQL]);
        }

        $query->expects($this->once())->method('execute')->willReturn([]);

        $queryService->execute($query, $options);
    }

    /**
     * @return array|\array[][]
     */
    public function getExecuteWillPrepareAndExecuteQueryData(): array
    {
        return [
            // Empty options
            [
                [],
            ],

            // Set parameters
            [
                [
                    'params' => [
                        'foo' => 'bar',
                        'baz' => 123,
                    ]
                ],
            ],

            // Hydration Mode
            [
                [
                    QueryServiceOption::HYDRATION_MODE => Query::HYDRATE_ARRAY,
                ]
            ],
        ];
    }

    /**
     * Assert that if an exception is raised when calling execute() that the exception is caught, logged and rethrown
     * as a QueryServiceException.
     *
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::execute
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::prepareQuery
     *
     * @throws QueryServiceException
     */
    public function testExecuteWillCatchAndThrowExceptionsASQueryServiceException(): void
    {
        $queryService = new QueryService($this->entityName, $this->entityManager, $this->logger);

        /** @var AbstractQuery|MockObject $query */
        $query = $this->createMock(AbstractQuery::class);

        $exceptionCode = 1234;
        $exceptionMessage = 'This is a example exception message';
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $query->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $errorMessage = sprintf('Failed to execute query : %s', $exceptionMessage);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, compact('exception'));

        $this->expectException(QueryServiceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exceptionCode);

        $queryService->execute($query);
    }

    /**
     * Assert that a valid query provided to execute() will be executed and the result set returned.
     *
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::execute
     * @covers \Arp\DoctrineEntityRepository\Query\QueryService::prepareQuery
     *
     * @throws QueryServiceException
     */
    public function testExecuteWillReturnResultSet(): void
    {
        $queryService = new QueryService($this->entityName, $this->entityManager, $this->logger);

        /** @var AbstractQuery|MockObject $query */
        $query = $this->createMock(AbstractQuery::class);

        /** @var EntityInterface[]|MockObject[] $resultSet */
        $resultSet = [
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class),
        ];

        $query->expects($this->once())
            ->method('execute')
            ->willReturn($resultSet);

        $this->assertSame($resultSet, $queryService->execute($query));
    }
}
