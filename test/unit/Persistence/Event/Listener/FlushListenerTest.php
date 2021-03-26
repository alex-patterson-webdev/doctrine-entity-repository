<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class FlushListenerTest extends TestCase
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
     * Assert that the class is callable
     */
    public function testIsCallable(): void
    {
        $listener = new FlushListener();

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that if calling __invoke() without flush mode being ENABLED we will not perform the flush operation
     *
     * @throws PersistenceException
     */
    public function testInvokeWillNotCallFlushIfFlushModeIsNotEnabled(): void
    {
        $listener = new FlushListener();

        $entityName = EntityInterface::class;
        $flushMode = FlushMode::DISABLED;

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::FLUSH_MODE, FlushMode::ENABLED)
            ->willReturn($flushMode);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Flush operations are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    FlushMode::DISABLED,
                    EntityEventOption::FLUSH_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::FLUSH_MODE => FlushMode::DISABLED]
            );

        // Assert that we do not call on the entity manager
        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that if calling __invoke() with flush mode ENABLED we will perform the flush operation
     *
     * @throws PersistenceException
     */
    public function testInvokeWillCallFlushIfFlushModeIsEnabled(): void
    {
        $listener = new FlushListener();

        $entityName = EntityInterface::class;
        $flushMode = FlushMode::ENABLED;

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::FLUSH_MODE, FlushMode::ENABLED)
            ->willReturn($flushMode);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Flush operations are enabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    FlushMode::ENABLED,
                    EntityEventOption::FLUSH_MODE
                )
            );

        /** @var PersistServiceInterface&MockObject $persistService */
        $persistService = $this->createMock(PersistServiceInterface::class);

        $event->expects($this->once())
            ->method('getPersistService')
            ->willReturn($persistService);

        $persistService->expects($this->once())->method('flush');

        $listener($event);
    }
}
