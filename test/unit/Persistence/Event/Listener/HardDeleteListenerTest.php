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
use Arp\EventDispatcher\Event\ParametersInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener
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
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the listener is callable
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener
     */
    public function testIsCallable(): void
    {
        $listener = new HardDeleteListener($this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if a NULL entity value if found then the listener will exit early.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener::__invoke
     *
     * @throws PersistenceException
     */
    public function testInvokeWillReturnEarlyWithNullEntity(): void
    {
        $listener = new HardDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $event->expects($this->never())->method('getEntityName');

        $listener($event);
    }

    /**
     * Assert that calls to __invoke() will exit early if the DeleteMode has been set to SOFT.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener::__invoke
     *
     * @throws PersistenceException
     */
    public function testInvokeWillNotCallEntityManagerRemoveIfDeleteModeIsSoft(): void
    {
        $listener = new HardDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var DeleteAwareInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(DeleteAwareInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $entityId = '12345';

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $deleteMode = DeleteMode::SOFT;

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::SOFT)
            ->willReturn($deleteMode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Delete mode \'%s\' detected : Skipping hard delete operations for entity \'%s::%s\'',
                    $deleteMode,
                    $entityName,
                    $entityId
                )
            );

        // We should not be calling on the entity manager.
        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that if the EntityManager::remove() call throws an exception the __invoke() method will catch, log
     * and rethrow a PersistenceException.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener::__invoke
     *
     * @throws PersistenceException
     */
    public function testInvokeWillCatchEntityManagerRemoveExceptionsAndRethrowAsPersistenceException(): void
    {
        $listener = new HardDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $entityId = '12345';

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $deleteMode = DeleteMode::HARD;

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::HARD)
            ->willReturn($deleteMode);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $exceptionMessage = 'This is a test exception message';
        $exceptionCode = 456;
        $exception = new \Exception($exceptionMessage, $exceptionCode);

        $entityManager->expects($this->once())
            ->method('remove')
            ->willThrowException($exception);

        $exceptionMessage = sprintf(
            'Failed to perform delete of entity \'%s::%s\' : %s',
            $entityName,
            $entityId,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exceptionMessage, ['exception' => $exception, 'entity_name' => $entityName, 'id' => $entityId]);

        $this->expectExceptionMessage(PersistenceException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->expectExceptionCode($exceptionCode);

        $listener($event);
    }

    /**
     * Assert that the __invoke() method will perform the entity manager remove if the delete mdoe is set to HARD.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\HardDeleteListener::__invoke
     *
     * @throws PersistenceException
     */
    public function testInvokeWillDeleteEntityWithDeleteModeHard(): void
    {
        $listener = new HardDeleteListener($this->logger);

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $entityId = '12345';

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $deleteMode = DeleteMode::HARD;

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DELETE_MODE, DeleteMode::HARD)
            ->willReturn($deleteMode);

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('remove')
            ->with($entity);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Successfully performed the hard delete operation for entity \'%s::%s\'',
                    $entityName,
                    $entityId
                )
            );

        $listener($event);
    }
}
