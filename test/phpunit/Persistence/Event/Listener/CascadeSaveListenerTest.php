<?php

declare(strict_types=1);

namespace Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\CascadeMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\EntityRepositoryProviderInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Persistence\Event\Listener
 */
final class CascadeSaveListenerTest extends TestCase
{
    /**
     * @var EntityRepositoryProviderInterface|MockObject
     */
    private EntityRepositoryProviderInterface $entityRepositoryProvider;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var array
     */
    private array $defaultSaveOptions = [];

    /**
     * @var array
     */
    private array $defaultCollectionSaveOptions = [];

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->entityRepositoryProvider = $this->getMockForAbstractClass(EntityRepositoryProviderInterface::class);

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the listener is a callable class.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\CascadeSaveListener
     */
    public function testIsCallable(): void
    {
        $listener = new CascadeSaveListener(
            $this->entityRepositoryProvider,
            $this->logger,
            $this->defaultSaveOptions,
            $this->defaultCollectionSaveOptions
        );

        $this->assertIsCallable($listener);
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

        $listener = new CascadeSaveListener(
            $this->entityRepositoryProvider,
            $this->logger,
            $this->defaultSaveOptions,
            $this->defaultCollectionSaveOptions
        );

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
                    'Ignoring cascade save operations with mode \'%s\' for entity \'%s\'',
                    $cascadeMode,
                    $entityName
                )
            );

        $event->expects($this->never())->method('getEntityManager');

        $this->assertNull($listener($event));
    }


    public function testInvokeWillCallSaveAssociations(): void
    {
        $entityName = EntityInterface::class;
        $cascadeMode = CascadeMode::ALL;

        $listener = new CascadeSaveListener(
            $this->entityRepositoryProvider,
            $this->logger,
            $this->defaultSaveOptions,
            $this->defaultCollectionSaveOptions
        );

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
    }
}
