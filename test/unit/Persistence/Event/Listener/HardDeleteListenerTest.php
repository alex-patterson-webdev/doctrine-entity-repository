<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\DeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\DeleteAwareInterface;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class HardDeleteListenerTest extends TestCase
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
     * Assert that the listener is callable
     */
    public function testIsCallable(): void
    {
        $listener = new HardDeleteListener();

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if a NULL entity value if found then the listener will exit early
     */
    public function testInvokeWillReturnEarlyWithNullEntity(): void
    {
        $listener = new HardDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $event->expects($this->never())->method('getEntityName');

        $listener($event);
    }

    /**
     * Assert that calls to __invoke() will exit early if the DeleteMode has been set to SOFT
     */
    public function testInvokeWillNotCallEntityManagerRemoveIfDeleteModeIsSoft(): void
    {
        $listener = new HardDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->createMock(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $deleteMode = DeleteMode::SOFT;

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::SOFT)
            ->willReturn($deleteMode);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Delete operations are disabled for entity \'%s\' '
                    . 'using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    $deleteMode,
                    EntityEventOption::DELETE_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::DELETE_MODE => $deleteMode],
            );

        // We should not be calling on the entity manager.
        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that the __invoke() method will perform the entity manager remove if the delete mode is set to HARD
     */
    public function testInvokeWillDeleteEntityWithDeleteModeHard(): void
    {
        $listener = new HardDeleteListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $deleteMode = DeleteMode::HARD;

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::HARD)
            ->willReturn($deleteMode);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('remove')
            ->with($entity);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Successfully performed the delete operation for entity \'%s\'', $entityName));

        $listener($event);
    }
}
