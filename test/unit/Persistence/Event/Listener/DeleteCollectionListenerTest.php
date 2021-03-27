<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Persistence\Event\CollectionEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DeleteCollectionListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DeleteCollectionListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
class DeleteCollectionListenerTest extends TestCase
{
    /**
     * @var PersistServiceInterface&MockObject
     */
    private $persistService;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->persistService = $this->createMock(PersistServiceInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the event listener is callable
     */
    public function testIsCallable(): void
    {
        $listener = new DeleteCollectionListener();

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that the listener will iterate and save the provided collection
     *
     * @param array<mixed> $options
     *
     * @throws PersistenceException
     * @throws \Exception
     */
    public function testInvokeWillDeleteTheProvidedCollection(array $options = []): void
    {
        $defaultOptions = [
            EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
            EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED,
            EntityEventOption::CLEAR_MODE       => ClearMode::DISABLED,
            EntityEventOption::PERSIST_MODE     => PersistMode::ENABLED,
        ];

        $options = array_replace_recursive($defaultOptions, $options);

        $listener = new DeleteCollectionListener($options);

        $entityName = EntityInterface::class;

        /** @var array<EntityInterface&MockObject> $deleteCollection */
        $deleteCollection = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        /** @var CollectionEvent&MockObject $event */
        $event = $this->createMock(CollectionEvent::class);

        $event->expects($this->once())
            ->method('getCollection')
            ->willReturn($deleteCollection);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Deleting \'%s\' collection', $entityName));

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::COLLECTION_DELETE_OPTIONS, [])
            ->willReturn($options[EntityEventOption::COLLECTION_DELETE_OPTIONS] ?? []);

        $event->expects($this->once())
            ->method('getPersistService')
            ->willReturn($this->persistService);

        $deletedCount = 0;
        $saveArgs = $returnArgs = [];
        foreach ($deleteCollection as $index => $entity) {
            $saveArgs[] = [$entity, $options];
            $isDeleted = (random_int(0, 100) >= 50);
            $returnArgs[] = $isDeleted;
            if ($isDeleted) {
                $deletedCount++;
            }
        }

        $this->persistService->expects($this->exactly(count($deleteCollection)))
            ->method('delete')
            ->withConsecutive(...$saveArgs)
            ->willReturnOnConsecutiveCalls(...$returnArgs);

        /** @var ParametersInterface<mixed>&MockObject $params */
        $params = $this->createMock(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('setParam')
            ->with('deleted_count', $deletedCount);

        $listener($event);
    }
}
