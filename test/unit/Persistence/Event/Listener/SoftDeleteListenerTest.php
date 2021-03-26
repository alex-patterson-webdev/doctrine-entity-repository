<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener;
use Arp\Entity\DeleteAwareInterface;
use Arp\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class SoftDeleteListenerTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the listener is of a callable type
     */
    public function testIsCallable(): void
    {
        $listener = new SoftDeleteListener();

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that providing NULL as the entity will result in the operation being logged and ignored
     */
    public function testNullEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new SoftDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->logger->info(
            sprintf(
                'Ignoring soft delete for entity \'%s\': The entity value is either null '
                . 'or this entity has not configured to be able to perform soft deletes',
                $entityName
            )
        );

        $listener($event);
    }

    /**
     * Assert that providing an instance that is not implementing DeleteAwareInterface will result in the
     * operation being logged and ignored
     */
    public function testNonDeleteAwareEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new SoftDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->logger->info(
            sprintf(
                'Ignoring soft delete for entity \'%s\': The entity value is either null '
                . 'or this entity has not configured to be able to perform soft deletes',
                $entityName
            )
        );

        $listener($event);
    }

    /**
     * Assert that already deleted entities are logged and ignored (no further updated required)
     */
    public function testAlreadyDeletedEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new SoftDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->createMock(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('isDeleted')
            ->willReturn(true); // Already deleted

        $this->logger->info(
            sprintf(
                'Ignoring soft delete operations for already deleted entity \'%s\'',
                $entityName
            )
        );

        $entity->expects($this->never())->method('setDeleted');

        $listener($event);
    }

    /**
     * Assert that the soft delete listener will ignore/cancel soft delete operations if the provide 'delete mode'
     * is set to 'hard'. This action should also be logged with the injected logger
     */
    public function testSoftDeleteIsDisabledIfDeleteModeIsHard(): void
    {
        $deleteMode = DeleteMode::HARD;

        $listener = new SoftDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->createMock(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('isDeleted')
            ->willReturn(false);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::SOFT)
            ->willReturn($deleteMode);

        $this->logger->info(
            sprintf(
                'Soft deleting has been disabled for entity \'%s\' via configuration option \'%s\'',
                $entityName,
                EntityEventOption::DELETE_MODE
            )
        );

        $entity->expects($this->never())->method('setDeleted');

        $listener($event);
    }

    /**
     * Assert that the SoftDeleteListener will correctly set the entity to deleted status.
     *
     * @param string|null $deleteMode
     *
     * @dataProvider getSoftDeleteData
     */
    public function testSoftDelete(?string $deleteMode = null): void
    {
        $listener = new SoftDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->createMock(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('isDeleted')
            ->willReturn(false);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::SOFT)
            ->willReturn(($deleteMode ?? DeleteMode::SOFT));

        $this->logger->info(sprintf('Saving \'%s\' collection', $entityName));

        $entity->expects($this->once())
            ->method('setDeleted')
            ->with(true);

        $listener($event);
    }

    /**
     * @return array<mixed>
     */
    public function getSoftDeleteData(): array
    {
        return [
            [
                null, // we expect the default to use SOFT deleting...
            ],
            [
                DeleteMode::SOFT,
            ],
        ];
    }
}
