<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\ErrorListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class ErrorListenerTest extends TestCase
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
     * Assert that the class implements AggregateListenerInterface/
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\ErrorListener
     */
    public function testImplementsAggregateListenerInterface(): void
    {
        $listener = new ErrorListener($this->logger);

        $this->assertInstanceOf(AggregateListenerInterface::class, $listener);
    }

    /**
     * Assert that the addListeners() method will register the onError() event listener with the
     * Create, Update and Delete events.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\ErrorListener::addListeners
     *
     * @throws EventListenerException
     */
    public function testAddListenersWillRegisterTheOnErrorEventWithCreateUpdateAndDeleteEvents(): void
    {
        $listener = new ErrorListener($this->logger);

        /** @var AddListenerAwareInterface|MockObject $collection */
        $collection = $this->getMockForAbstractClass(AddListenerAwareInterface::class);

        $collection->expects($this->exactly(3))
            ->method('addListenerForEvent')
            ->withConsecutive(
                [EntityEventName::CREATE_ERROR, [$listener, 'onError'], -1000],
                [EntityEventName::UPDATE_ERROR, [$listener, 'onError'], -1000],
                [EntityEventName::DELETE_ERROR, [$listener, 'onError'], -1000]
            );

        $listener->addListeners($collection);
    }

    /**
     * Assert that the onError() method will overwrite any exception that is not of type PersistenceException
     * and reset the exception on the provided event.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\ErrorListener::onError
     */
    public function testOnErrorWillOverwriteSetExceptionWithNewPersistenceException(): void
    {
        $entityName = EntityInterface::class;
        $eventName = EntityEventName::UPDATE;

        $listener = new ErrorListener($this->logger);

        /** @var EntityErrorEvent|MockObject $event */
        $event = $this->createMock(EntityErrorEvent::class);

        $exceptionMessage = 'This is a test exception message!';
        $exception = new \Exception($exceptionMessage);

        $event->expects($this->once())
            ->method('getException')
            ->willReturn($exception);

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $exceptionMessage = sprintf(
            'An error occurred while performing the \'%s\' event for entity class \'%s\': %s',
            $eventName,
            $entityName,
            $exceptionMessage
        );

        $event->expects($this->once())
            ->method('setException')
            ->with($this->isInstanceOf(PersistenceException::class));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exceptionMessage, $this->arrayHasKey('exception'));

        $listener->onError($event);
    }
}
