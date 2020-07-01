<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\EntityRepository;
use Arp\DoctrineEntityRepository\EntityRepositoryInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
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
class EntityRepositoryTest extends TestCase
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

        $exceptionMessage = 'This is a test exception message';
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
}
