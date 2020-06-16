<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\PersistListener;
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
final class PersistListenerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
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
     * Assert that the class is callable.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\PersistListener
     */
    public function testIsCallable(): void
    {
        $listener = new PersistListener($this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that a PersistenceException will be thrown from __invoke if the provided event instance doesn't
     * contain a valid entity instance.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\PersistListener::__invoke
     * @throws PersistenceException
     */
    public function testInvokeWillThrowPersistenceExceptionIfEntityIsNull(): void
    {
        $listener = new PersistListener($this->logger);

        $entityName = EntityInterface::class;

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $errorMessage = sprintf(
            'Unable to perform entity persist operation for entity of type \'%s\': The entity is null',
            $entityName
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, compact('entityName'));

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $listener($event);
    }

    /**
     * Assert that if the PersistMode is not set to ENABLED then the listener will exit without calling the
     * entity manager's persist. The listener should also log that this happened.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\PersistListener::__invoke
     *
     * @throws PersistenceException
     */
    public function testInvokeWillNotPersistEntityIfPersistModeIsNotEnabled(): void
    {
        $listener = new PersistListener($this->logger);

        $entityName = EntityInterface::class;

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $persistMode = PersistMode::DISABLED; // will raise early exit condition..

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::PERSIST_MODE, PersistMode::ENABLED)
            ->willReturn($persistMode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Skipping persist operation for entity \'%s\' with persist mode \'%s\'',
                    $entityName,
                    $persistMode
                ),
                compact('entityName', 'persistMode')
            );

        // Assert that we do NOT call on the entity manager
        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that if the PersistMode is set to ENABLED then the listener will perform the
     * entity manager's persist. The listener should also log that this happened.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\PersistListener::__invoke
     *
     * @throws PersistenceException
     */
    public function testInvokeWillPersistEntityWhenPersistModeIsSetToEnabled(): void
    {
        $listener = new PersistListener($this->logger);

        $entityName = EntityInterface::class;

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $persistMode = PersistMode::ENABLED; // will raise early exit condition..

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::PERSIST_MODE, PersistMode::ENABLED)
            ->willReturn($persistMode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Performing persist operation for entity \'%s\' with persist mode \'%s\'',
                    $entityName,
                    $persistMode
                ),
                compact('entityName', 'persistMode')
            );

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $listener($event);
    }
}
