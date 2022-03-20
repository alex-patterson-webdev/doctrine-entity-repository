<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Persistence\Event\CollectionEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\SaveCollectionListener;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\SaveCollectionListener
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class SaveCollectionListenerTest extends TestCase
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
        $listener = new SaveCollectionListener();

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that the listener will iterate and save the provided collection
     *
     * @param array<mixed> $options
     *
     * @throws PersistenceException
     */
    public function testInvokeWillSaveTheProvidedCollection(array $options = []): void
    {
        $defaultOptions = [
            EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
            EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED,
            EntityEventOption::CLEAR_MODE       => ClearMode::DISABLED,
            EntityEventOption::PERSIST_MODE     => PersistMode::ENABLED,
        ];

        $options = array_replace_recursive($defaultOptions, $options);

        $listener = new SaveCollectionListener($options);

        $entityName = EntityInterface::class;

        /** @var array<EntityInterface&MockObject> $collection */
        $collection = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        /** @var array<EntityInterface&MockObject> $returnCollection */
        $returnCollection = [
            $collection[0],
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        /** @var CollectionEvent&MockObject $event */
        $event = $this->createMock(CollectionEvent::class);

        $event->expects($this->once())
            ->method('getCollection')
            ->willReturn($collection);

        $event->expects($this->once())
            ->method('getLogger')
            ->willReturn($this->logger);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Saving \'%s\' collection', $entityName));

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getParam')
            ->with('collection_save_options', [])
            ->willReturn($options['collection_save_options'] ?? []);

        $event->expects($this->once())
            ->method('getPersistService')
            ->willReturn($this->persistService);

        $saveArgs = $returnArgs = [];
        foreach ($collection as $index => $entity) {
            $saveArgs[] = [$entity, $options];
            $returnArgs[] = $returnCollection[$index];
        }

        $this->persistService->expects($this->exactly(count($collection)))
            ->method('save')
            ->withConsecutive(...$saveArgs)
            ->willReturnOnConsecutiveCalls(...$returnArgs);

        $listener($event);
    }
}
