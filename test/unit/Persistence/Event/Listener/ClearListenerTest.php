<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\ClearListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\ClearListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class ClearListenerTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * Set up the test case dependencies.
     */
    public function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the listener is callable
     */
    public function testIsCallable(): void
    {
        $listener = new ClearListener();

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that the entity manager will not be cleared if the clear mode is set to DISABLED
     *
     * @throws PersistenceException
     */
    public function testDisabledClearModeWillNotCallEntityManagerClear(): void
    {
        $listener = new ClearListener();

        $entityName = EntityInterface::class;
        $clearMode = ClearMode::DISABLED;

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::CLEAR_MODE, ClearMode::DISABLED)
            ->willReturn($clearMode);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Clearing operations are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    $clearMode,
                    EntityEventOption::CLEAR_MODE
                ),
                ['entity_name' => $entityName, EntityEventOption::CLEAR_MODE => ClearMode::DISABLED]
            );

        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that the entity manager clear will be called when providing clear mode ENABLED to __invoke()
     *
     * @throws PersistenceException
     */
    public function testEnabledClearModeWillClearEntityManager(): void
    {
        $listener = new ClearListener();

        $entityName = EntityInterface::class;
        $clearMode = ClearMode::ENABLED;

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
            ->with(EntityEventOption::CLEAR_MODE, ClearMode::DISABLED)
            ->willReturn($clearMode);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Clear operation completed successfully for entity \'%s\'', $entityName));

        /** @var PersistServiceInterface&MockObject $persistService */
        $persistService = $this->createMock(PersistServiceInterface::class);

        $event->expects($this->once())
            ->method('getPersistService')
            ->willReturn($persistService);

        $persistService->expects($this->once())->method('clear');

        $listener($event);
    }
}
