<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener;
use Arp\Entity\DeleteAwareInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
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
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the listener is of a callable type
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener
     */
    public function testIsCallable(): void
    {
        $listener = new SoftDeleteListener($this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that providing NULL as the entity will result in the operation being logged and ignored.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener::__invoke
     */
    public function testNullEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new SoftDeleteListener($this->logger);

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
     * operation being logged and ignored.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener::__invoke
     */
    public function testNonDeleteAwareEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new SoftDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

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
     * Assert that already deleted entities are logged and ignored (no further updated required).
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener::__invoke
     */
    public function testAlreadyDeletedEntityWillBeLoggedAndIgnored(): void
    {
        $listener = new SoftDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC';

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('isDeleted')
            ->willReturn(true); // Already deleted

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->logger->info(
            sprintf(
                'Ignoring soft delete operations for already deleted entity \'%s::%s\'',
                $entityName,
                $entityId
            )
        );

        $entity->expects($this->never())->method('setDeleted');

        $listener($event);
    }

    /**
     * Assert that the soft delete listener will ignore/cancel soft delete operations if the provide 'delete mode'
     * is set to 'hard'. This action should also be logged with the injected logger.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener::__invoke
     */
    public function testSoftDeleteIsDisabledIfDeleteModeIsHard(): void
    {
        $deleteMode = DeleteMode::HARD;

        $listener = new SoftDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('isDeleted')
            ->willReturn(false);

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
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
     * @covers       \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SoftDeleteListener::__invoke
     * @dataProvider getSoftDeleteData
     */
    public function testSoftDelete(?string $deleteMode = null): void
    {
        $listener = new SoftDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $entityName = EntityInterface::class;
        $entityId = 'ABC';

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('isDeleted')
            ->willReturn(false);

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::SOFT)
            ->willReturn(($deleteMode ?? DeleteMode::SOFT));

        $this->logger->info(
            sprintf(
                'Performing \'%s\' delete for entity \'%s::%s\'',
                DeleteMode::SOFT,
                $entityName,
                $entityId
            )
        );

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
