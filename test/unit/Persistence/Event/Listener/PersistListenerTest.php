<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\PersistListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\Event\Listener\PersistListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class PersistListenerTest extends TestCase
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
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the class is callable
     */
    public function testIsCallable(): void
    {
        $listener = new PersistListener();

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that a PersistenceException will be thrown from __invoke if the provided event instance doesn't
     * contain a valid entity instance
     *
     * @throws PersistenceException
     */
    public function testInvokeWillThrowPersistenceExceptionIfEntityIsNull(): void
    {
        $listener = new PersistListener();

        $entityName = EntityInterface::class;

        /** @var EntityEvent&MockObject $event */
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

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, compact('entityName'));

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $listener($event);
    }

    /**
     * Assert that if the PersistMode is not set to ENABLED then the listener will exit without calling the
     * entity manager's persist. The listener should also log that this happened
     *
     * @throws PersistenceException
     */
    public function testInvokeWillNotPersistEntityIfPersistModeIsNotEnabled(): void
    {
        $listener = new PersistListener();

        $entityName = EntityInterface::class;

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $persistMode = PersistMode::DISABLED; // will raise early exit condition..

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::PERSIST_MODE, PersistMode::ENABLED)
            ->willReturn($persistMode);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Persist operations are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    PersistMode::DISABLED,
                    EntityEventOption::PERSIST_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::PERSIST_MODE => PersistMode::DISABLED]
            );

        // Assert that we do NOT call on the entity manager
        $event->expects($this->never())->method('getPersistService');

        $listener($event);
    }

    /**
     * Assert that if the PersistMode is set to ENABLED then the listener will perform the
     * entity manager's persist. The listener should also log that this happened
     *
     * @throws PersistenceException
     */
    public function testInvokeWillPersistEntityWhenPersistModeIsSetToEnabled(): void
    {
        $listener = new PersistListener();

        $entityName = EntityInterface::class;

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        /** @var EntityInterface&MockObject $entity */
        $entity = $this->createMock(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $persistMode = PersistMode::ENABLED; // will raise early exit condition

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::PERSIST_MODE, PersistMode::ENABLED)
            ->willReturn($persistMode);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Flush operations are enabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    PersistMode::ENABLED,
                    EntityEventOption::PERSIST_MODE
                )
            );

        /** @var PersistServiceInterface&MockObject $persistService */
        $persistService = $this->createMock(PersistServiceInterface::class);

        $event->expects($this->once())
            ->method('getPersistService')
            ->willReturn($persistService);

        $persistService->expects($this->once())
            ->method('persist')
            ->with($entity);

        $listener($event);
    }
}
