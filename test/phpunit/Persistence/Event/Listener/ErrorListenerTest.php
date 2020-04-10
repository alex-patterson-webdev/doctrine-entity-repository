<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Persistence\Event\Listener\ErrorListener;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
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
}
