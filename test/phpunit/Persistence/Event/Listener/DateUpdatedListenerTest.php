<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactory;
use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateUpdatedListenerTest extends TestCase
{
    /**
     * @var DateTimeFactory|MockObject
     */
    private $dateTimeFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->dateTimeFactory = $this->getMockForAbstractClass(DateTimeFactoryInterface::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Assert that the class is callable.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateUpdatedListener
     */
    public function testIsCallable(): void
    {
        $listener = new DateUpdatedListener($this->dateTimeFactory, $this->logger);

        $this->assertIsCallable($listener);
    }
}
