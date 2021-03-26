<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\CascadeSaveService;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\AggregateEntityInterface;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class CascadeSaveListenerTest extends TestCase
{
    /**
     * @var CascadeSaveService&MockObject
     */
    private CascadeSaveService $cascadeSaveService;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->cascadeSaveService = $this->createMock(CascadeSaveService::class);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the listener is a callable class
     */
    public function testIsCallable(): void
    {
        $listener = new CascadeSaveListener($this->cascadeSaveService);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that a PersistenceException is thrown if the event cannot provided a valid entity instance.
     *
     * @param mixed $entity The entity value that should be tested.
     *
     * @dataProvider getInvokeWillNotSaveAssociationsIfEntityDoesNotImplementAggregateEntityInterfaceData
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testInvokeWillNotSaveAssociationsIfEntityDoesNotImplementAggregateEntityInterface($entity): void
    {
        $listener = new CascadeSaveListener($this->cascadeSaveService);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->cascadeSaveService->expects($this->never())->method('saveAssociations');

        $listener($event);
    }

    /**
     * @return array<mixed>
     */
    public function getInvokeWillNotSaveAssociationsIfEntityDoesNotImplementAggregateEntityInterfaceData(): array
    {
        return [
            [null],
            [$this->getMockForAbstractClass(EntityInterface::class)],
        ];
    }

    /**
     * Assert that __invoke() will NOT execute the cascade save operations if the provided cascade mode
     * has been set to NONE
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testInvokeWillNotCascadeSaveIfCascadeModeIsNone(): void
    {
        $entityName = EntityInterface::class;
        $cascadeMode = CascadeMode::NONE;

        $listener = new CascadeSaveListener($this->cascadeSaveService);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var AggregateEntityInterface&MockObject $entity */
        $entity = $this->createMock(AggregateEntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::CASCADE_MODE, CascadeMode::ALL)
            ->willReturn($cascadeMode);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Ignoring cascade save operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );

        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that the event listener will execute the cascade save services saveAssociations() method with a valid
     * cascade mode provided.
     *
     * @param string|null  $cascadeMode
     * @param array<mixed> $options
     * @param array<mixed> $collectionOptions
     *
     * @dataProvider getCascadeSaveData
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testCascadeSave(?string $cascadeMode, array $options = [], array $collectionOptions = []): void
    {
        if (null === $cascadeMode) {
            $cascadeMode = CascadeMode::ALL;
        }

        if (CascadeMode::ALL !== $cascadeMode && CascadeMode::SAVE !== $cascadeMode) {
            $this->fail(
                sprintf(
                    'Invalid test case \'cascadeMode\'. The \'%s\' '
                    . 'test expects either \'%s\' or \'%s\' as valid test values',
                    __METHOD__,
                    CascadeMode::SAVE,
                    CascadeMode::ALL
                )
            );
        }

        $entityName = AggregateEntityInterface::class;

        $listener = new CascadeSaveListener($this->cascadeSaveService);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(AggregateEntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                [EntityEventOption::CASCADE_MODE, CascadeMode::ALL],
                [EntityEventOption::CASCADE_SAVE_OPTIONS, []],
                [EntityEventOption::CASCADE_SAVE_COLLECTION_OPTIONS, []]
            )->willReturnOnConsecutiveCalls(
                $cascadeMode,
                $options,
                $collectionOptions
            );

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Performing cascade save operations for entity \'%s\'', $entityName));

        $this->cascadeSaveService->expects($this->once())
            ->method('saveAssociations')
            ->with($entityManager, $entityName, $entity, $options, $collectionOptions);

        $listener($event);
    }

    /**
     * @return array|array[]
     */
    public function getCascadeSaveData(): array
    {
        return [
            [
                null,
            ],
            [
                CascadeMode::SAVE,
            ],
            [
                CascadeMode::ALL,
            ],
        ];
    }
}
