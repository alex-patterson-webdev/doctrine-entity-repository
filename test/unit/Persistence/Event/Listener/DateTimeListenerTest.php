<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateDeletedListener;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateTimeListener;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateTimeListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateTimeListenerTest extends TestCase
{
    /**
     * @var DateCreatedListener&MockObject
     */
    private $dateCreatedListener;

    /**
     * @var DateUpdatedListener&MockObject
     */
    private $dateUpdateListener;

    /**
     * @var DateDeletedListener&MockObject
     */
    private $dateDeletedListener;

    /**
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->dateCreatedListener = $this->createMock(DateCreatedListener::class);
        $this->dateUpdateListener = $this->createMock(DateUpdatedListener::class);
        $this->dateDeletedListener = $this->createMock(DateDeletedListener::class);
    }

    /**
     * Assert that the listener will register the DateCreateListener, DateUpdatedListener and DateDeleteListener
     * with the provided AddListenerAwareInterface.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateTimeListener::__construct
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateTimeListener::addListeners
     *
     * @throws EventListenerException
     */
    public function testAddListenersWillRegisterTheDateCreatedAndDateUpdatedListeners(): void
    {
        $listener = new DateTimeListener(
            $this->dateCreatedListener,
            $this->dateUpdateListener,
            $this->dateDeletedListener
        );

        /** @var AddListenerAwareInterface&MockObject $collection */
        $collection = $this->getMockForAbstractClass(AddListenerAwareInterface::class);

        $collection->expects($this->exactly(3))
            ->method('addListenerForEvent')
            ->withConsecutive(
                [EntityEventName::CREATE, $this->dateCreatedListener, 10],
                [EntityEventName::UPDATE, $this->dateUpdateListener, 10],
                [EntityEventName::DELETE, $this->dateDeletedListener, 10]
            );

        $listener->addListeners($collection);
    }
}
