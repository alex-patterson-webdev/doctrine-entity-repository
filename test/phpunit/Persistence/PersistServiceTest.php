<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\PersistServiceOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistService;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\EventDispatcher;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
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
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var EventDispatcher|MockObject
     */
    private $eventDispatcher;

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
     * Assert that if we pass an entity with an id to save() that the entity will be updated and returned.
     *
     * @param array $options Optional save options that should be passed to the updated method.
     *
     * @dataProvider getSaveWillUpdateAndReturnEntityWithIdData
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::save
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::update
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::dispatchEvent
     *
     * @throws PersistenceException
     */
    public function testSaveWillUpdateAndReturnEntityWithId(array $options = []): void
    {
        /** @var PersistService|MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs(
                [
                    $this->entityName,
                    $this->entityManager,
                    $this->eventDispatcher,
                    $this->logger
                ]
            )->onlyMethods(['createEvent'])
            ->getMock();

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(true);

        /** @var EntityEvent|MockObject $event */
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
     * @return array
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
                ]
            ]
        ];
    }

    /**
     * Assert that an entity provided to save() that does not have an identity will be proxies to insert().
     *
     * @param array $options
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::save
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::insert
     * @covers \Arp\DoctrineEntityRepository\Persistence\PersistService::dispatchEvent
     *
     * @dataProvider getSaveWillInsertAndReturnEntityWithNoIdData
     *
     * @throws PersistenceException
     */
    public function testSaveWillInsertAndReturnEntityWithNoId(array $options = []): void
    {
        /** @var PersistService|MockObject $persistService */
        $persistService = $this->getMockBuilder(PersistService::class)
            ->setConstructorArgs(
                [
                    $this->entityName,
                    $this->entityManager,
                    $this->eventDispatcher,
                    $this->logger
                ]
            )->onlyMethods(['createEvent'])
            ->getMock();

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entity->expects($this->once())
            ->method('hasId')
            ->willReturn(false);

        /** @var EntityEvent|MockObject $event */
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
     * @return array
     */
    public function getSaveWillInsertAndReturnEntityWithNoIdData(): array
    {
        return [
            [

            ]
        ];
    }
}
