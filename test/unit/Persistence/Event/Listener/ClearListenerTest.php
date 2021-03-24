<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\ClearListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use Doctrine\ORM\EntityManager;
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
     * Assert that the entity manager will not be cleared if the clear mode is set to DISABLED.
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

        /** @var ParametersInterface<mixed>&MockObject $parameters */
        $parameters = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $parameters->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::CLEAR_MODE, ClearMode::DISABLED)
            ->willReturn($clearMode);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Clear mode is disabled for entity \'%s\'; skipping entity manager clear operations',
                    $entityName
                )
            );

        $event->expects($this->never())->method('getEntityManager');

        $listener($event);
    }

    /**
     * Assert that the entity manager clear will be called when providing clear mode ENABLED to __invoke().
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

        /** @var ParametersInterface<mixed>&MockObject $parameters */
        $parameters = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $parameters->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::CLEAR_MODE, ClearMode::DISABLED)
            ->willReturn($clearMode);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Performing entity manager clear operations for entity class \'%s\'',
                    $entityName
                )
            );

        /** @var EntityManager&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

        $event->expects($this->once())->method('getEntityManager')->willReturn($entityManager);
        $entityManager->expects($this->once())->method('clear');

        $listener($event);
    }
}
