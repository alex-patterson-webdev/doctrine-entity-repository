<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\CollectionEvent;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Resolver\EventNameAwareInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\CollectionEvent
 * @covers \Arp\DoctrineEntityRepository\Persistence\Event\AbstractEntityEvent
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event
 */
final class CollectionEventTest extends TestCase
{
    /**
     * @var string
     */
    private string $eventName;

    /**
     * @var PersistServiceInterface&MockObject
     */
    private $persistService;

    /**
     * @var EntityManagerInterface&MockObject
     */
    private $entityManager;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var array<mixed>
     */
    private array $params = [];

    /**
     * Prepare the test case dependencies
     */
    public function setUp(): void
    {
        $this->eventName = EntityEventName::UPDATE;

        $this->persistService = $this->createMock(PersistServiceInterface::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Assert that the class implements EventNameAwareInterface
     */
    public function testImplementsEventNameAwareInterface(): void
    {
        $event = new CollectionEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            $this->params
        );

        $this->assertInstanceOf(EventNameAwareInterface::class, $event);
    }

    /**
     * Assert that a iterable collection can be get/set on the collection via getCollection() and setCollection()
     *
     * @param iterable<EntityInterface&MockObject> $collection
     *
     * @dataProvider getSetAndGetCollectionData
     */
    public function testSetAndGetCollection(iterable $collection): void
    {
        $event = new CollectionEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            $this->params
        );

        $event->setCollection($collection);

        $this->assertSame($collection, $event->getCollection());
    }

    /**
     * @return array<mixed>
     */
    public function getSetAndGetCollectionData(): array
    {
        $arrayCollection = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        $objectCollection = new \ArrayIterator($arrayCollection);

        return [
            [
                $arrayCollection,
            ],
            [
                $objectCollection
            ]
        ];
    }

    /**
     * Assert that calls to count() will return the collection size
     *
     * @param iterable<EntityInterface&MockObject> $collection
     *
     * @dataProvider getCountWillReturnCollectionSizeData
     */
    public function testCountWillReturnCollectionSize(iterable $collection): void
    {
        $event = new CollectionEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            $this->params
        );

        $countExpected = ($collection instanceof \Traversable)
            ? count(iterator_to_array($collection))
            : count($collection);

        $event->setCollection($collection);

        $this->assertSame($countExpected, $event->getCount());
    }

    /**
     * @return array<mixed>
     */
    public function getCountWillReturnCollectionSizeData(): array
    {
        $arrayCollection = [
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
            $this->createMock(EntityInterface::class),
        ];

        $objectCollection = new \ArrayIterator($arrayCollection);

        return [
            [
                $arrayCollection,
            ],
            [
                $objectCollection
            ]
        ];
    }
}
