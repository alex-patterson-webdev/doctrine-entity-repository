<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\EventDispatcher\Resolver\EventNameAwareInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event
 */
final class EntityErrorEventTest extends TestCase
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
     * @var \Throwable
     */
    private \Throwable $exception;

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
        $this->eventName = EntityEventName::CREATE_ERROR;

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->persistService = $this->createMock(PersistServiceInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->exception = new \Exception('This is a test exception message');
    }

    /**
     * Assert that the event implement EventNameAwareInterface
     */
    public function testIsInstanceOfEventNameAwareInterface(): void
    {
        $event = new EntityErrorEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            $this->exception,
            $this->params
        );

        $this->assertInstanceOf(EventNameAwareInterface::class, $event);
    }

    /**
     * Assert that hasException() will return TRUE when the exception instance is set
     */
    public function testHasExceptionWillReturnTrueIfExceptionIsSet(): void
    {
        $event = new EntityErrorEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            $this->exception,
            $this->params
        );

        $this->assertTrue($event->hasException());
    }

    /**
     * Assert that hasException() will return FALSE when the exception instance is NOT set
     */
    public function testHasExceptionWillReturnFalseIFNoExceptionIsSet(): void
    {
        $event = new EntityErrorEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            null,
            $this->params
        );

        $this->assertFalse($event->hasException());
    }

    /**
     * Assert the constructor set exception class is returned from getException()
     */
    public function testGetExceptionWillReturnException(): void
    {
        $event = new EntityErrorEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            $this->exception,
            $this->params
        );

        $this->assertSame($this->exception, $event->getException());
    }

    /**
     * Assert that the exception can be set and get via setException() and getException()
     */
    public function testSetAndGetException(): void
    {
        $event = new EntityErrorEvent(
            $this->eventName,
            $this->persistService,
            $this->entityManager,
            $this->logger,
            $this->exception,
            $this->params
        );

        $newException = new \Exception('This is a new exception message');

        $event->setException($newException);

        $this->assertSame($newException, $event->getException());
    }
}
