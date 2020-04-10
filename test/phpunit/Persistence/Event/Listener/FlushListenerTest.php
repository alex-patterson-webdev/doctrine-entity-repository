<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class FlushListenerTest extends TestCase
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
     * Assert that the class is callable.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\FlushListener
     */
    public function testIsCallable(): void
    {
        $listener = new FlushListener($this->logger);

        $this->assertIsCallable($listener);
    }
}
