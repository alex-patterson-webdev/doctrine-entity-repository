<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Query;

use Arp\DoctrineEntityRepository\Query\QueryService;
use Arp\DoctrineEntityRepository\Query\QueryServiceInterface;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
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
    private $entityName;

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
}
