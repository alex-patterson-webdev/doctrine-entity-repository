<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\CascadeDeleteService;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeDeleteListener;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
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
final class CascadeDeleteListenerTest extends TestCase
{
    /**
     * @var CascadeDeleteService|MockObject
     */
    private $cascadeDeleteService;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->cascadeDeleteService = $this->createMock(CascadeDeleteService::class);

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the listener is a callable class.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeDeleteListener
     */
    public function testIsCallable(): void
    {
        $listener = new CascadeDeleteListener($this->cascadeDeleteService, $this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that a PersistenceException is thrown if the event cannot provided a valid entity instance.
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testInvokeWillThrowPersistenceExceptionAndLogInvalidEntity(): void
    {
        /** @var CascadeSaveListener|MockObject $listener */
        $listener = new CascadeDeleteListener($this->cascadeDeleteService, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entity = null;

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $errorMessage = sprintf('Missing required entity in \'%s\'', CascadeDeleteListener::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $this->assertNull($listener($event));
    }

    /**
     * Assert that __invoke() will NOT execute the cascade delete operations if the provided cascade mode
     * has been set to NONE.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeDeleteListener::__invoke
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testInvokeWillNotCascadeDeleteIfCascadeModeIsNone(): void
    {
        $entityName = EntityInterface::class;
        $cascadeMode = CascadeMode::NONE;

        $listener = new CascadeDeleteListener($this->cascadeDeleteService, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

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
                    'Ignoring cascade delete operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );

        $event->expects($this->never())->method('getEntityManager');

        $this->assertNull($listener($event));
    }

    /**
     * Assert that the event listener will execute the cascade delete services deleteAssociations() method with a valid
     * cascade mode provided.
     *
     * @param string|null $cascadeMode
     * @param array       $options
     * @param array       $collectionOptions
     *
     * @dataProvider getCascadeDeleteData
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeDeleteListener::__invoke
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testCascadeDelete(?string $cascadeMode, array $options = [], array $collectionOptions = []): void
    {
        if (null === $cascadeMode) {
            $cascadeMode = CascadeMode::ALL;
        }

        if (CascadeMode::ALL !== $cascadeMode && CascadeMode::DELETE !== $cascadeMode) {
            $this->fail(
                sprintf(
                    'Invalid test case \'cascadeMode\'. The \'%s\' '
                    . 'test expects either \'%s\' or \'%s\' as valid test values',
                    __METHOD__,
                    CascadeMode::DELETE,
                    CascadeMode::ALL
                )
            );
        }

        $entityName = EntityInterface::class;

        $listener = new CascadeDeleteListener($this->cascadeDeleteService, $this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

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
                [EntityEventOption::CASCADE_DELETE_OPTIONS, []],
                [EntityEventOption::CASCADE_DELETE_COLLECTION_OPTIONS, []]
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
            ->with(sprintf('Performing cascade delete operations for entity \'%s\'', $entityName));

        $this->cascadeDeleteService->expects($this->once())
            ->method('deleteAssociations')
            ->with($entityManager, $entityName, $entity, $options, $collectionOptions);

        $this->assertNull($listener($event));
    }

    /**
     * @return array|array[]
     */
    public function getCascadeDeleteData(): array
    {
        return [
            [
                null,
            ],
            [
                CascadeMode::DELETE,
            ],
            [
                CascadeMode::ALL,
            ],
        ];
    }

}
