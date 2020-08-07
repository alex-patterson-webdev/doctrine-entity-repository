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
use Arp\EventDispatcher\Event\ParametersInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class CascadeSaveListenerTest extends TestCase
{
    /**
     * @var CascadeSaveService|MockObject
     */
    private CascadeSaveService $cascadeSaveService;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $logger;

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->cascadeSaveService = $this->createMock(CascadeSaveService::class);

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the listener is a callable class.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener
     */
    public function testIsCallable(): void
    {
        $listener = new CascadeSaveListener($this->cascadeSaveService, $this->logger);

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
        /** @var CascadeSaveListener|MockObject $listener */
        $listener = new CascadeSaveListener($this->cascadeSaveService, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->cascadeSaveService->expects($this->never())->method('saveAssociations');

        $this->assertNull($listener($event));
    }

    /**
     * @return array
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
     * has been set to NONE.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener::__invoke
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testInvokeWillNotCascadeSaveIfCascadeModeIsNone(): void
    {
        $entityName = EntityInterface::class;
        $cascadeMode = CascadeMode::NONE;

        $listener = new CascadeSaveListener($this->cascadeSaveService, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var AggregateEntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(AggregateEntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::CASCADE_MODE, CascadeMode::ALL)
            ->willReturn($cascadeMode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Ignoring cascade save operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );

        $event->expects($this->never())->method('getEntityManager');

        $this->assertNull($listener($event));
    }

    /**
     * Assert that the event listener will execute the cascade save services saveAssociations() method with a valid
     * cascade mode provided.
     *
     * @param string|null $cascadeMode
     * @param array       $options
     * @param array       $collectionOptions
     *
     * @dataProvider getCascadeSaveData
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener::__invoke
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

        $listener = new CascadeSaveListener($this->cascadeSaveService, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(AggregateEntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->exactly(3))
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

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(sprintf('Performing cascade save operations for entity \'%s\'', $entityName));

        $this->cascadeSaveService->expects($this->once())
            ->method('saveAssociations')
            ->with($entityManager, $entityName, $entity, $options, $collectionOptions);

        $this->assertNull($listener($event));
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
