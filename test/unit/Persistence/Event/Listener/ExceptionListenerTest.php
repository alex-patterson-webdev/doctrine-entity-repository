<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\ExceptionListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\ExceptionListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class ExceptionListenerTest extends TestCase
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
     * Assert that the class implements AggregateListenerInterface
     */
    public function testImplementsAggregateListenerInterface(): void
    {
        $listener = new ExceptionListener();

        $this->assertInstanceOf(AggregateListenerInterface::class, $listener);
    }

    /**
     * Assert that the addListeners() method will register the onError() event listener with the
     * Create, Update and Delete events
     *
     * @throws EventListenerException
     */
    public function testAddListenersWillRegisterTheOnErrorEventWithCreateUpdateAndDeleteEvents(): void
    {
        $listener = new ExceptionListener();

        /** @var AddListenerAwareInterface&MockObject $collection */
        $collection = $this->createMock(AddListenerAwareInterface::class);

        $collection->expects($this->exactly(5))
            ->method('addListenerForEvent')
            ->withConsecutive(
                [EntityEventName::CREATE_ERROR, [$listener, 'onError'], -1000],
                [EntityEventName::UPDATE_ERROR, [$listener, 'onError'], -1000],
                [EntityEventName::DELETE_ERROR, [$listener, 'onError'], -1000],
                [EntityEventName::SAVE_COLLECTION_ERROR, [$listener, 'onError'], -1000],
                [EntityEventName::DELETE_COLLECTION_ERROR, [$listener, 'onError'], -1000]
            );

        $listener->addListeners($collection);
    }

    /**
     * Assert that the onError() method will overwrite any exception that is not of type PersistenceException
     * and reset the exception on the provided event
     *
     * @param bool $logErrors
     * @param bool $throwExceptions
     *
     * @dataProvider getOnErrorWillOverwriteSetExceptionWithNewPersistenceExceptionData
     *
     * @throws PersistenceException
     * @throws \Throwable
     */
    public function testOnErrorWillOverwriteSetExceptionWithNewPersistenceException(
        bool $logErrors,
        bool $throwExceptions
    ): void {
        $entityName = EntityInterface::class;

        $listener = new ExceptionListener();

        /** @var EntityErrorEvent&MockObject $event */
        $event = $this->createMock(EntityErrorEvent::class);

        $internalExceptionMessage = 'This is a test exception message!';
        $exception = new \Exception($internalExceptionMessage);

        $event->expects($this->once())
            ->method('getException')
            ->willReturn($exception);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(
                [EntityEventOption::LOG_ERRORS, true],
                [EntityEventOption::THROW_EXCEPTIONS, true]
            )->willReturnOnConsecutiveCalls(
                $logErrors,
                $throwExceptions
            );

        $exceptionMessage = sprintf(
            'A persistence error occurred for entity class \'%s\': %s',
            $entityName,
            $internalExceptionMessage
        );

        if (true === $logErrors) {
            $event->expects($this->once())
                ->method('getLogger')
                ->willReturn($this->logger);


            $this->logger->expects($this->once())
                ->method('error')
                ->with($internalExceptionMessage, $this->arrayHasKey('exception'));
        }

        if (true === $throwExceptions) {
            $event->expects($this->once())
                ->method('setException')
                ->with($this->isInstanceOf(PersistenceException::class));

            $this->expectException(PersistenceException::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $listener->onError($event);
    }

    /**
     * @return array<mixed>
     */
    public function getOnErrorWillOverwriteSetExceptionWithNewPersistenceExceptionData(): array
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
    }
}
