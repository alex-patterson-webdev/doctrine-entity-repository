<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener;
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
final class FlushListenerTest extends TestCase
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
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener
     */
    public function testIsCallable(): void
    {
        $listener = new FlushListener($this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if calling __invoke() without flush mode being ENABLED we will not perform the flush operation.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener::__invoke
     */
    public function testInvokeWillNotCallFlushIfFlushModeIsNotEnabled(): void
    {
        $listener = new FlushListener($this->logger);

        $entityName = EntityInterface::class;
        $flushMode = FlushMode::DISABLED;

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

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
            ->with(EntityEventOption::FLUSH_MODE, FlushMode::ENABLED)
            ->willReturn($flushMode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Skipping flush operations for entity \'%s\' with mode \'%s\'',
                    $entityName,
                    $flushMode
                )
            );

        // Assert that we do not call on the entity manager
        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that if calling __invoke() with flush mode ENABLED we will perform the flush operation.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener::__invoke
     */
    public function testInvokeWillCallFlushIfFlushModeIsEnabled(): void
    {
        $listener = new FlushListener($this->logger);

        $entityName = EntityInterface::class;
        $flushMode = FlushMode::ENABLED;

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

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
            ->with(EntityEventOption::FLUSH_MODE, FlushMode::ENABLED)
            ->willReturn($flushMode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(sprintf('Performing flush operations with mode \'%s\'', $flushMode));

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())->method('flush');

        $listener($event);
    }
}
