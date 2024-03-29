<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\CascadeDeleteService;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeDeleteListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeDeleteListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class CascadeDeleteListenerTest extends TestCase
{
    /**
     * @var CascadeDeleteService&MockObject
     */
    private $cascadeDeleteService;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->cascadeDeleteService = $this->createMock(CascadeDeleteService::class);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the listener is a callable class
     */
    public function testIsCallable(): void
    {
        $listener = new CascadeDeleteListener($this->cascadeDeleteService);

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
        $listener = new CascadeDeleteListener($this->cascadeDeleteService);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entity = null;

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $errorMessage = sprintf('Missing required entity in \'%s\'', CascadeDeleteListener::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $listener($event);
    }

    /**
     * Assert that __invoke() will NOT execute the cascade delete operations if the provided cascade mode
     * has been set to NONE
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testInvokeWillNotCascadeDeleteIfCascadeModeIsNone(): void
    {
        $entityName = EntityInterface::class;
        $cascadeMode = CascadeMode::NONE;

        $listener = new CascadeDeleteListener($this->cascadeDeleteService);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

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
                    'The cascade delete operations are disabled for entity \'%s\' '
                    . 'using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    $cascadeMode,
                    EntityEventOption::CASCADE_MODE
                )
            );

        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that the event listener will execute the cascade delete services deleteAssociations() method with a valid
     * cascade mode provided.
     *
     * @param string|null  $cascadeMode
     * @param array<mixed> $options
     * @param array<mixed> $collectionOptions
     *
     * @dataProvider getCascadeDeleteData
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

        $listener = new CascadeDeleteListener($this->cascadeDeleteService);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

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
                [EntityEventOption::CASCADE_DELETE_OPTIONS, []],
                [EntityEventOption::CASCADE_DELETE_COLLECTION_OPTIONS, []]
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
            ->with(
                sprintf(
                    'Performing cascade delete operations for entity \'%s\' using \'%s\' configuration setting \'%s\'',
                    $entityName,
                    $cascadeMode,
                    EntityEventOption::CASCADE_MODE
                )
            );

        $this->cascadeDeleteService->expects($this->once())
            ->method('deleteAssociations')
            ->with($entityManager, $entityName, $entity, $options, $collectionOptions);

        $listener($event);
    }

    /**
     * @return array<mixed>
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
