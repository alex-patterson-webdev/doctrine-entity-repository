<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactory;
use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener;
use Arp\Entity\DateCreatedAwareInterface;
use Arp\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateCreatedListenerTest extends TestCase
{
    /**
     * @var DateTimeFactory|MockObject
     */
    private $dateTimeFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->dateTimeFactory = $this->getMockForAbstractClass(DateTimeFactoryInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the class is callable.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener
     */
    public function testIsCallable(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory, $this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that the date created will NOT be updated if the provided entity is not
     * of type DateCreatedAwareInterface.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener::__invoke
     *
     * @throws DateTimeFactoryException
     */
    public function testWillNotSetDateCreatedIfEntityIsNotOfTypeDateCreatedAwareInterface(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory, $this->logger);

        $entityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Ignoring created date operations : \'%s\' does not implement \'%s\'.',
                    $entityName,
                    DateCreatedAwareInterface::class
                )
            );

        $this->dateTimeFactory->expects($this->never())->method('createDateTime');

        $listener($event);
    }

//    public function testWillNotSetDateCreatedIfDateCreatedModeIsDisabled(): void
//    {
//    }

}
