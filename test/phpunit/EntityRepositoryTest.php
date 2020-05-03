<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\EntityRepository;
use Arp\DoctrineEntityRepository\EntityRepositoryInterface;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\DoctrineEntityRepository\Query\QueryServiceInterface;
use Arp\Entity\EntityInterface;
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
    private $entityName;

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
     * @covers \Arp\DoctrineEntityRepository\EntityRepository
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
}
