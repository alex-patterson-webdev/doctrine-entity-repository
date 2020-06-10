<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use Arp\EventDispatcher\Listener\AddListenerAwareInterface;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use Arp\EventDispatcher\Listener\Exception\EventListenerException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class TransactionListenerTest extends TestCase
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
     * Assert that the class implements AggregateListenerInterface.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener
     */
    public function testIsInstanceOfAggregateListenerInterface(): void
    {
        $listener = new TransactionListener($this->logger);

        $this->assertInstanceOf(AggregateListenerInterface::class, $listener);
    }

    /**
     * Assert that the expected event listeners are attached to the provided listener collection.
     *
     * @throws EventListenerException
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::addListeners
     */
    public function testAddListenersWillRegisterEventListenersWithProvidedCollection(): void
    {
        $listener = new TransactionListener($this->logger);

        /** @var AddListenerAwareInterface|MockObject $collection */
        $collection = $this->getMockForAbstractClass(AddListenerAwareInterface::class);

        $collection->expects($this->exactly(9))
            ->method('addListenerForEvent')
            ->withConsecutive(
                [EntityEventName::CREATE, [$listener, 'beginTransaction'], 900],
                [EntityEventName::CREATE, [$listener, 'commitTransaction'], 1],
                [EntityEventName::CREATE_ERROR, [$listener, 'rollbackTransaction'], 1000],

                [EntityEventName::UPDATE, [$listener, 'beginTransaction'], 900],
                [EntityEventName::UPDATE, [$listener, 'commitTransaction'], 1],
                [EntityEventName::UPDATE_ERROR, [$listener, 'rollbackTransaction'], 1000],

                [EntityEventName::DELETE, [$listener, 'beginTransaction'], 900],
                [EntityEventName::DELETE, [$listener, 'commitTransaction'], 1],
                [EntityEventName::DELETE_ERROR, [$listener, 'rollbackTransaction'], 1000],
            );

        $listener->addListeners($collection);
    }

    /**
     * Assert that the beingTransaction() will not start a transaction if the event listener has been disabled
     * by configuration option EntityEventOption::TRANSACTION_MODE.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::beginTransaction
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::isEnabled
     */
    public function testBeginTransactionWillNotStartATransactionIfTheTransactionsHaveBeenDisabled(): void
    {
        $transactionMode = TransactionMode::DISABLED;

        $listener = new TransactionListener($this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::CREATE;

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);
        $entityId = 'ABC123';

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Skipping \'%s::%s\' operation for entity \'%s::%s\' with mode \'%s\'',
                    $eventName,
                    'beginTransaction',
                    $entityName,
                    $entityId,
                    $transactionMode
                ),
                compact('eventName', 'entityName', 'transactionMode')
            );

        $listener->beginTransaction($event);
    }

    /**
     * Assert that the commitTransaction() will not commit a transaction if the event listener has been disabled
     * by configuration option EntityEventOption::TRANSACTION_MODE.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::commitTransaction
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::isEnabled
     */
    public function testCommitTransactionWillNotCommitTheTransactionIfTheTransactionsHaveBeenDisabled(): void
    {
        $transactionMode = TransactionMode::DISABLED;

        $listener = new TransactionListener($this->logger);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::CREATE;

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);
        $entityId = 'ABC123';

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Skipping \'%s::%s\' operation for entity \'%s::%s\' with mode \'%s\'',
                    $eventName,
                    'commitTransaction',
                    $entityName,
                    $entityId,
                    $transactionMode
                ),
                compact('eventName', 'entityName', 'transactionMode')
            );

        $listener->commitTransaction($event);
    }

    /**
     * Assert that the rollbackTransaction() will not rollback a transaction if the event listener has been disabled
     * by configuration option EntityEventOption::TRANSACTION_MODE.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::rollbackTransaction
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::isEnabled
     */
    public function testRollbackTransactionWillNotRollbackIfTheTransactionsHaveBeenDisabled(): void
    {
        $transactionMode = TransactionMode::DISABLED;

        $listener = new TransactionListener($this->logger);

        /** @var EntityErrorEvent|MockObject $errorEvent */
        $errorEvent = $this->createMock(EntityErrorEvent::class);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $errorEvent->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $errorEvent->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::CREATE;

        $errorEvent->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $entityId = '0';

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Skipping \'%s::%s\' operation for entity \'%s::%s\' with mode \'%s\'',
                    $eventName,
                    'rollbackTransaction',
                    $entityName,
                    $entityId,
                    $transactionMode
                ),
                compact('eventName', 'entityName', 'transactionMode')
            );

        $listener->rollbackTransaction($errorEvent);
    }

    /**
     * Assert that the beingTransaction() method will correctly start a new transaction with the event's event manager.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::beginTransaction
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::isEnabled
     */
    public function testBeginTransaction(): void
    {
        $transactionMode = TransactionMode::ENABLED;

        $listener = new TransactionListener($this->logger);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE)
            ->willReturn($transactionMode);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::UPDATE;

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Starting a new \'%s\' transaction for entity \'%s::%s\'',
                    $eventName,
                    $entityName,
                    $entityId
                )
            );

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())->method('beginTransaction');

        $listener->beginTransaction($event);
    }

    /**
     * Assert that the commitTransaction() method will correctly commit a new transaction
     * with the event's event manager.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::commitTransaction
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::isEnabled
     */
    public function testCommitTransaction(): void
    {
        $transactionMode = TransactionMode::ENABLED;

        $listener = new TransactionListener($this->logger);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE)
            ->willReturn($transactionMode);

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $entityName = EntityInterface::class;
        $entityId = 'ABC123';

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::UPDATE;

        $event->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        $entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Committing \'%s\' transaction for entity \'%s::%s\'',
                    $eventName,
                    $entityName,
                    $entityId
                )
            );

        $event->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())->method('commit');

        $listener->commitTransaction($event);
    }

    /**
     * Assert that the rollbackTransaction() method will correctly rollback the transaction
     * with a call to the event's event manager.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::rollbackTransaction
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener::isEnabled
     */
    public function testRollbackTransaction(): void
    {
        $transactionMode = TransactionMode::ENABLED;

        $listener = new TransactionListener($this->logger);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        /** @var EntityErrorEvent|MockObject $errorEvent */
        $errorEvent = $this->createMock(EntityErrorEvent::class);

        $errorEvent->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::TRANSACTION_MODE)
            ->willReturn($transactionMode);

        $entityName = EntityInterface::class;

        $errorEvent->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $eventName = EntityEventName::UPDATE;

        $errorEvent->expects($this->once())
            ->method('getEventName')
            ->willReturn($eventName);

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Rolling back \'%s\' transaction for entity class \'%s\'',
                    $eventName,
                    $entityName
                )
            );

        $errorEvent->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())->method('rollback');

        $listener->rollbackTransaction($errorEvent);
    }
}
