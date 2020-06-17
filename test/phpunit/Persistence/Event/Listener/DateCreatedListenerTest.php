<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DateTime\DateTimeFactory;
use Arp\DateTime\DateTimeFactoryInterface;
use Arp\DateTime\Exception\DateTimeFactoryException;
use Arp\DoctrineEntityRepository\Constant\DateCreatedMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Persistence\Event\EntityEvent;
use Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener;
use Arp\Entity\DateCreatedAwareInterface;
use Arp\Entity\EntityInterface;
use Arp\EventDispatcher\Event\ParametersInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DateCreatedListenerTest extends TestCase
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
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener
     */
    public function testIsCallable(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory, $this->logger);

        $this->assertIsCallable($listener);
    }

    /**
     * Assert that the date created will NOT be updated if the provided entity is not
     * of type DateCreatedAwareInterface.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener::__invoke
     *
     * @throws DateTimeFactoryException
     */
    public function testWillNotSetDateCreatedIfEntityIsNotOfTypeDateCreatedAwareInterface(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory, $this->logger);

        $entityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                sprintf(
                    'Ignoring created date operations : \'%s\' does not implement \'%s\'.',
                    $entityName,
                    DateCreatedAwareInterface::class
                )
            );

        $this->dateTimeFactory->expects($this->never())->method('createDateTime');

        $listener($event);
    }

    /**
     * Assert that if the entity event option DATE_CREATED_MODE is not set to ENABLED then the listener
     * will not set a new DateCreated date.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener::__invoke
     *
     * @throws DateTimeFactoryException
     */
    public function testWillNotSetDateCreatedIfDateCreatedModeIsDisabled(): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory, $this->logger);

        $entityName = DateCreatedAwareInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(DateCreatedAwareInterface::class);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $mode = DateCreatedMode::DISABLED;

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_CREATED_MODE, DateCreatedMode::ENABLED)
            ->willReturn($mode);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Ignoring created date operations : \'%s\' has a date create mode set to \'%s\'.',
                    $entityName,
                    $mode
                )
            );

        $this->dateTimeFactory->expects($this->never())->method('createDateTime');

        $listener($event);
    }

    /**
     * Assert that __invoke() will set the DateCreated date if the required DateCreatedMode::ENABLED mode is provided
     *
     * @param string $mode The EntityEventOption::DATE_CREATED_MODE that should be tested.
     *
     * @dataProvider getWillSetDateCreatedData
     * @covers \Arp\DoctrineEntityRepository\Persistence\Event\Listener\DateCreatedListener::__invoke
     *
     * @throws DateTimeFactoryException
     */
    public function testWillSetDateCreated(?string $mode): void
    {
        $listener = new DateCreatedListener($this->dateTimeFactory, $this->logger);

        $entityName = DateCreatedAwareInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(DateCreatedAwareInterface::class);

        /** @var EntityEvent|MockObject $event */
        $event = $this->createMock(EntityEvent::class);

        $event->expects($this->once())
            ->method('getEntityName')
            ->willReturn($entityName);

        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        /** @var ParametersInterface|MockObject $params */
        $params = $this->getMockForAbstractClass(ParametersInterface::class);

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);

        $params->expects($this->once())
            ->method('getParam')
            ->with(EntityEventOption::DATE_CREATED_MODE, DateCreatedMode::ENABLED)
            ->will(isset($mode) ? $this->returnValue($mode) : $this->returnArgument(1));

        $createdDateTime = new \DateTime();

        $this->dateTimeFactory->expects($this->once())
            ->method('createDateTime')
            ->willReturn($createdDateTime);

        $entity->expects($this->once())
            ->method('setDateCreated')
            ->with($createdDateTime);

        $createdDateTimeString = $createdDateTime->format(\DateTime::ATOM);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                sprintf(
                    'Setting new created date \'%s\' for entity of type \'%s\'',
                    $createdDateTimeString,
                    $entityName
                )
            );

        $listener($event);
    }

    /**
     * @return array
     */
    public function getWillSetDateCreatedData(): array
    {
        return [
            [
                null, // enabled is the default, so we expect that without passing an option..
            ],
            [
                DateCreatedMode::ENABLED,
            ]
        ];
    }
}
