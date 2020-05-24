<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Persistence\Event\Listener\EntityValidationListener;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class EntityValidationListenerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Set up the test case dependencies.
     */
    public function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the class implements AggregateListenerInterface
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\EntityValidationListener
     */
    public function testImplementsAggregateListenerInterface(): void
    {
        $listener = new EntityValidationListener($this->logger);

        $this->assertInstanceOf(AggregateListenerInterface::class, $listener);
    }
}
