<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\Constant\EntityEventName;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Resolver\EventNameAwareInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers  \Arp\DoctrineEntityRepository\Persistence\Event\EntityErrorEvent
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence
 */
final class EntityErrorEventTest extends TestCase
{
    /**
     * @var string
     */
    private string $eventName;

    /**
     * @var string
     */
    private string $entityName = EntityInterface::class;

    /**
     * @var EntityManagerInterface&MockObject
     */
    private $entityManager;

    /**
     * @var \Throwable
     */
    private \Throwable $exception;

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

        $this->exception = new \Exception('This is a test exception message');
    }

    /**
     * Assert that the event implement EventNameAwareInterface
     */
    public function testIsInstanceOfEventNameAwareInterface(): void
    {
        $event = new EntityErrorEvent(
            $this->eventName,
            $this->entityName,
            $this->entityManager,
            $this->exception,
            $this->params
        );

        $this->assertInstanceOf(EventNameAwareInterface::class, $event);
    }

    /**
     * Assert the constructor set exception class is returned from getException()
     */
    public function testGetExceptionWillReturnException(): void
    {
        $event = new EntityErrorEvent(
            $this->eventName,
            $this->entityName,
            $this->entityManager,
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
            $this->entityName,
            $this->entityManager,
            $this->exception,
            $this->params
        );

        $newException = new \Exception('This is a new exception message');

        $event->setException($newException);

        $this->assertSame($newException, $event->getException());
    }
}