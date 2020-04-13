<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Persistence\Event\Listener\TransactionListener;
use Arp\EventDispatcher\Listener\AggregateListenerInterface;
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
}
