<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class TransactionListenerTest extends TestCase
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
    public function testIsInstanceOfAggregateListenerInterface(): void
    {
        $listener = new TransactionListener();

        $this->assertInstanceOf(AggregateListenerInterface::class, $listener);
    }

    /**
     * Assert that the expected event listeners are attached to the provided listener collection.
     *
     * @throws EventListenerException
     */
    public function testAddListenersWillRegisterEventListenersWithProvidedCollection(): void
    {
        $listener = new TransactionListener();

        /** @var AddListenerAwareInterface&MockObject $collection */
        $collection = $this->createMock(AddListenerAwareInterface::class);

        $collection->expects($this->exactly(15))
            ->method('addListenerForEvent')
            ->withConsecutive(
                [EntityEventName::CREATE, [$listener, 'beginTransaction'], 900],
                [EntityEventName::UPDATE, [$listener, 'beginTransaction'], 900],
                [EntityEventName::DELETE, [$listener, 'beginTransaction'], 900],
                [EntityEventName::SAVE_COLLECTION, [$listener, 'beginTransaction'], 900],
                [EntityEventName::DELETE_COLLECTION, [$listener, 'beginTransaction'], 900],
                [EntityEventName::CREATE, [$listener, 'commitTransaction']],
                [EntityEventName::UPDATE, [$listener, 'commitTransaction']],
                [EntityEventName::DELETE, [$listener, 'commitTransaction']],
                [EntityEventName::SAVE_COLLECTION, [$listener, 'commitTransaction']],
                [EntityEventName::DELETE_COLLECTION, [$listener, 'commitTransaction']],
                [EntityEventName::CREATE_ERROR, [$listener, 'rollbackTransaction'], 1000],
                [EntityEventName::UPDATE_ERROR, [$listener, 'rollbackTransaction'], 1000],
                [EntityEventName::DELETE_ERROR, [$listener, 'rollbackTransaction'], 1000],
                [EntityEventName::SAVE_COLLECTION_ERROR, [$listener, 'rollbackTransaction'], 1000],
                [EntityEventName::DELETE_COLLECTION_ERROR, [$listener, 'rollbackTransaction'], 1000],
            );

        $listener->addListeners($collection);
    }

    /**
     * Assert that the beingTransaction() will not start a transaction if the event listener has been disabled
     * by configuration option EntityEventOption::TRANSACTION_MODE
     */
    public function testBeginTransactionWillNotStartATransactionIfTheTransactionsHaveBeenDisabled(): void
    {
        $transactionMode = TransactionMode::DISABLED;
        $defaultMode = TransactionMode::ENABLED;

        $listener = new TransactionListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE, $defaultMode)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

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
                    'Transactions are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    TransactionMode::DISABLED,
                    EntityEventOption::TRANSACTION_MODE
                ),
                [
                    'entity_name' => $entityName,
                    EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED
                ]
            );

        $listener->beginTransaction($event);
    }

    /**
     * Assert that the commitTransaction() will not commit a transaction if the event listener has been disabled
     * by configuration option EntityEventOption::TRANSACTION_MODE
     */
    public function testCommitTransactionWillNotCommitTheTransactionIfTheTransactionsHaveBeenDisabled(): void
    {
        $transactionMode = TransactionMode::DISABLED;
        $defaultMode = TransactionMode::ENABLED;

        $listener = new TransactionListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE, $defaultMode)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

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
                    'Transactions are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    TransactionMode::DISABLED,
                    EntityEventOption::TRANSACTION_MODE
                ),
                [
                    'entity_name' => $entityName,
                    EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED
                ]
            );

        $listener->commitTransaction($event);
    }

    /**
     * Assert that the rollbackTransaction() will not rollback a transaction if the event listener has been disabled
     * by configuration option EntityEventOption::TRANSACTION_MODE
     */
    public function testRollbackTransactionWillNotRollbackIfTheTransactionsHaveBeenDisabled(): void
    {
        $transactionMode = TransactionMode::DISABLED;
        $defaultMode = TransactionMode::ENABLED;

        $listener = new TransactionListener();

        /** @var EntityErrorEvent&MockObject $errorEvent */
        $errorEvent = $this->createMock(EntityErrorEvent::class);

        $errorEvent->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE, $defaultMode)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $errorEvent->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $errorEvent->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Transactions are disabled for entity \'%s\' using \'%s\' for configuration setting \'%s\'',
                    $entityName,
                    TransactionMode::DISABLED,
                    EntityEventOption::TRANSACTION_MODE
                ),
                [
                    'entity_name' => $entityName,
                    EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED
                ]
            );

        $listener->rollbackTransaction($errorEvent);
    }

    /**
     * Assert that the beingTransaction() method will correctly start a new transaction with the event's event manager
     */
    public function testBeginTransaction(): void
    {
        $eventName = EntityEventName::UPDATE;
        $transactionMode = TransactionMode::ENABLED;
        $defaultMode = TransactionMode::ENABLED;

        $listener = new TransactionListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE, $defaultMode)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $event->expects($this->exactly(2))
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        /** @var PersistServiceInterface&MockObject $persistService */
        $persistService = $this->createMock(PersistServiceInterface::class);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Starting a new \'%s\' transaction for entity \'%s\'', $eventName, $entityName));

        $event->expects($this->once())
            ->method('getPersistService')
            ->willReturn($persistService);

        $persistService->expects($this->once())->method('beginTransaction');

        $listener->beginTransaction($event);
    }

    /**
     * Assert that the commitTransaction() method will correctly commit a new transaction
     * with the event's event manager
     */
    public function testCommitTransaction(): void
    {
        $transactionMode = TransactionMode::ENABLED;

        $listener = new TransactionListener();

        /** @var EntityEvent&MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE, $transactionMode)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $event->expects($this->exactly(2))
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::UPDATE;

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Committing \'%s\' transaction for entity \'%s\'', $eventName, $entityName));

        /** @var PersistServiceInterface&MockObject $persistService */
        $persistService = $this->createMock(PersistServiceInterface::class);

        $event->expects($this->once())
            ->method('getPersistService')
            ->willReturn($persistService);

        $persistService->expects($this->once())->method('commitTransaction');

        $listener->commitTransaction($event);
    }

    /**
     * Assert that the rollbackTransaction() method will correctly rollback the transaction
     * with a call to the event's event manager
     */
    public function testRollbackTransaction(): void
    {
        $transactionMode = TransactionMode::ENABLED;

        $listener = new TransactionListener();

        /** @var EntityErrorEvent&MockObject $errorEvent */
        $errorEvent = $this->createMock(EntityErrorEvent::class);

        $errorEvent->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $errorEvent->expects($this->exactly(2))
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::UPDATE;

        $errorEvent->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $errorEvent->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Rolling back \'%s\' transaction for entity class \'%s\'', $eventName, $entityName));

        /** @var PersistServiceInterface&MockObject $persistService */
        $persistService = $this->createMock(PersistServiceInterface::class);

        $errorEvent->expects($this->once())
            ->method('getPersistService')
            ->willReturn($persistService);

        $persistService->expects($this->once())->method('rollbackTransaction');

        $listener->rollbackTransaction($errorEvent);
    }
}
