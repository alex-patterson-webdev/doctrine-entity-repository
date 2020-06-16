<?php

declare(strict_types=1);

namespace ArpTest\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\EntityRepositoryInterface;
use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\DoctrineEntityRepository\Persistence\CascadeSaveService;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Arp\Entity\EntityTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package ArpTest\DoctrineEntityRepository\Persistence
 */
final class CascadeSaveServiceTest extends TestCase
{
    /**
     * @var EntityManagerInterface|MockObject
     */
    private $entityManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var array
     */
    private array $options = [];

    /**
     * @var array
     */
    private array $collectionOptions = [];

    /**
     * Prepare the test case dependencies.
     */
    public function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);
    }

    /**
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::__construct
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociation
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::getTargetRepository
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function testSaveAssociationWillThrowAPersistenceExceptionIfTheTargetEntityRepositoryCannotBeLoaded(): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $entityName = EntityInterface::class;

        /** @var EntityInterface $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $exceptionMessage = 'This is a test exception message';
        $exception = new \Exception($exceptionMessage, 123);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($entityName)
            ->willThrowException($exception);

        $errorMessage = sprintf(
            'An error occurred while attempting to load the repository for entity class \'%s\' : %s',
            $entityName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage, compact('exception'));

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($exception->getCode());

        $cascadeService->saveAssociation($this->entityManager, $entityName, $entity);
    }

    /**
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::__construct
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociation
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::getTargetRepository
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function testSaveAssociationWillThrowAPersistenceExceptionIfTheTargetEntityRepositoryIsInvalid(): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $entityName = EntityInterface::class;

        /** @var EntityInterface $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $entityRepository = new \stdClass();

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($entityRepository);

        $errorMessage = sprintf(
            'The entity repository must be an object of type \'%s\'; \'%s\' returned in \'%s::%s\'',
            EntityRepositoryInterface::class,
            (is_object($entityRepository) ? get_class($entityRepository) : gettype($entityRepository)),
            CascadeSaveService::class,
            'getTargetRepository'
        );

        $this->logger->expects($this->once())->method('error')->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $cascadeService->saveAssociation($this->entityManager, $entityName, $entity);
    }

    /**
     * Assert that an PersistenceException is thrown if an invalid entity or collection value is
     * passed to the saveAssociation() method.
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::__construct
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociation
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::getTargetRepository
     *
     * @throws PersistenceException
     * @throws EntityRepositoryException
     */
    public function testSaveAssociationWillThrowAPersistenceExceptionIfTheTargetEntityOrCollectionIsInvalid(): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $targetEntityName = EntityInterface::class;

        /** @var EntityInterface|\stdClass $entityOrCollection */
        $entityOrCollection = new \stdClass();

        $errorMessage = sprintf(
            'Unable to cascade save target entity \'%s\': The entity or collection is of an invalid type \'%s\'',
            $targetEntityName,
            \stdClass::class
        );

        $this->logger->expects($this->once())->method('error')->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $cascadeService->saveAssociation($this->entityManager, $targetEntityName, $entityOrCollection);
    }

    /**
     * @param array $options
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::__construct
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociation
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::getTargetRepository
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testSaveAssociationWillSaveEntity(array $options = []): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $targetEntityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entityOrCollection */
        $entityOrCollection = $this->getMockForAbstractClass(EntityInterface::class);

        /** @var EntityRepositoryInterface|MockObject $entityRepository */
        $entityRepository = $this->getMockForAbstractClass(EntityRepositoryInterface::class);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($targetEntityName)
            ->willReturn($entityRepository);

        $entityRepository->expects($this->once())
            ->method('save')
            ->with($entityOrCollection, $options);

        $cascadeService->saveAssociation($this->entityManager, $targetEntityName, $entityOrCollection, $options);
    }

    /**
     * @param array $options
     *
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::__construct
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociation
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::getTargetRepository
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testSaveAssociationWillSaveEntityCollection(array $options = []): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $targetEntityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entityOrCollection */
        $entityOrCollection = [
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class),
            $this->getMockForAbstractClass(EntityInterface::class)
        ];

        /** @var EntityRepositoryInterface|MockObject $entityRepository */
        $entityRepository = $this->getMockForAbstractClass(EntityRepositoryInterface::class);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($targetEntityName)
            ->willReturn($entityRepository);

        $entityRepository->expects($this->once())
            ->method('saveCollection')
            ->with($entityOrCollection, $options);

        $cascadeService->saveAssociation($this->entityManager, $targetEntityName, $entityOrCollection, $options);
    }

    /**
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::__construct
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociations
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::getClassMetadata
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testSaveAssociationsWillThrowAPersistenceExceptionIfTheEntityMetadataCannotBeLoaded(): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $entityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        $exceptionMessage = 'This is a test exception message';
        $exception = new \Exception($exceptionMessage, 123);

        $errorMessage = $errorMessage = sprintf(
            'The entity metadata mapping for class \'%s\' could not be loaded: %s',
            $entityName,
            $exceptionMessage
        );

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode(123);

        $cascadeService->saveAssociations($this->entityManager, $entityName, $entity);
    }

    /**
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociations
     * @covers \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::resolveTargetEntityOrCollection
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testSaveAssociationsWillThrowAPersistenceExceptionIfTheTargetEntityMethodDoesNotExist(): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $entityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        /**
         * @var ClassMetadata|MockObject $classMetadata
         * @var ClassMetadata|MockObject $targetMetadata
         */
        $classMetadata = $this->createMock(ClassMetadata::class);
        $targetMetadata = $this->createMock(ClassMetadata::class);

        $mapping = [
            'targetEntity' => EntityInterface::class,
            'fieldName' => 'test',
            'type' => 'string',
            'isCascadePersist' => true,
        ];

        $mappings = [$mapping];

        $this->entityManager->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->withConsecutive(
                [$entityName],
                [$mapping['targetEntity']]
            )
            ->willReturnOnConsecutiveCalls(
                $classMetadata,
                $targetMetadata
            );

        $classMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn($mappings);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    sprintf('Processing cascade save operations for for entity class \'%s\'', $entityName)
                ],
                [
                    sprintf(
                        'The entity field \'%s::%s\' is configured for cascade operations for target entity \'%s\'',
                        $entityName,
                        $mapping['fieldName'],
                        $mapping['targetEntity']
                    )
                ]
            );

        $classMetadata->expects($this->once())->method('getName')->willReturn($entityName);
        $targetMetadata->expects($this->once())->method('getName')->willReturn($mapping['targetEntity']);

        $methodName = 'get' . ucfirst($mapping['fieldName']);

        $errorMessage = sprintf(
            'Failed to find required entity method \'%s::%s\'. The method is required for cascade operations '
            . 'of field \'%s\' of target entity \'%s\'',
            $entityName,
            $methodName,
            $mapping['fieldName'],
            $mapping['targetEntity']
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $cascadeService->saveAssociations($this->entityManager, $entityName, $entity);
    }

    /**
     * Assert that calls to saveAssociations() will raise a PersistenceException if the provided entity method call
     * throws an exception
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testSaveAssociationsWillThrowAPersistenceExceptionIfTheTargetEntityCannotBeLoaded(): void
    {
        $cascadeService = new CascadeSaveService($this->logger, $this->options, $this->collectionOptions);

        $entityName = EntityInterface::class;

        $exceptionMessage = 'This is a test exception message';
        $exception = new \Error($exceptionMessage);

        /** @var EntityInterface|MockObject $entity */
        $entity = new class($exception) implements EntityInterface {
            use EntityTrait;
            public \Throwable $exception;

            public function __construct(\Throwable $exception)
            {
                $this->exception = $exception;
            }

            public function getFoo(): string
            {
                throw $this->exception;
            }
        };

        /**
         * @var ClassMetadata|MockObject $classMetadata
         * @var ClassMetadata|MockObject $targetMetadata
         */
        $classMetadata = $this->createMock(ClassMetadata::class);
        $targetMetadata = $this->createMock(ClassMetadata::class);

        $mapping = [
            'targetEntity' => EntityInterface::class,
            'fieldName' => 'foo',
            'type' => 'string',
            'isCascadePersist' => true,
        ];

        $mappings = [$mapping];

        $this->entityManager->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->withConsecutive(
                [$entityName],
                [$mapping['targetEntity']]
            )
            ->willReturnOnConsecutiveCalls(
                $classMetadata,
                $targetMetadata
            );

        $classMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn($mappings);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    sprintf(
                        'Processing cascade save operations for for entity class \'%s\'',
                        $entityName
                    )
                ],
                [
                    sprintf(
                        'The entity field \'%s::%s\' is configured for cascade operations for target entity \'%s\'',
                        $entityName,
                        $mapping['fieldName'],
                        $mapping['targetEntity']
                    )
                ]
            );

        $classMetadata->expects($this->once())->method('getName')->willReturn($entityName);
        $targetMetadata->expects($this->once())->method('getName')->willReturn($mapping['targetEntity']);

        $methodName = 'get' . ucfirst($mapping['fieldName']);

        $errorMessage = sprintf(
            'The call to resolve entity of type \'%s\' from method call \'%s::%s\' failed: %s',
            $mapping['targetEntity'],
            $entityName,
            $methodName,
            $exceptionMessage
        );

        $this->logger->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionMessage($errorMessage);

        $cascadeService->saveAssociations($this->entityManager, $entityName, $entity);
    }

    /**
     * Assert that calls to saveAssociations() when mapping data contains associations that are either incorrectly
     * configured (missing required keys) or are not cascade persist.
     *
     * @param array $mappingData The association mapping data for a single field to test
     *
     * @covers       \Arp\DoctrineEntityRepository\Persistence\CascadeSaveService::saveAssociations
     *
     * @dataProvider getSaveAssociationsWillSkipAssociationsWithNonCascadePersistMappingDataData
     *
     * @throws EntityRepositoryException
     * @throws PersistenceException
     */
    public function testSaveAssociationsWillSkipAssociationsWithNonCascadePersistMappingData(array $mappingData): void
    {
        /** @var CascadeSaveService|MockObject $cascadeService */
        $cascadeService = $this->getMockBuilder(CascadeSaveService::class)
            ->setConstructorArgs([$this->logger, $this->options, $this->collectionOptions])
            ->onlyMethods(['saveAssociation'])
            ->getMock();

        $entityName = EntityInterface::class;

        /** @var EntityInterface|MockObject $entity */
        $entity = $this->getMockForAbstractClass(EntityInterface::class);

        /** @var ClassMetadata|MockObject $classMetadata */
        $classMetadata = $this->createMock(ClassMetadata::class);

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->willReturn($classMetadata);

        $classMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([$mappingData]);

        $cascadeService->saveAssociations($this->entityManager, $entityName, $entity);
    }

    /**
     * @return array
     */
    public function getSaveAssociationsWillSkipAssociationsWithNonCascadePersistMappingDataData(): array
    {
        return [
            [
                [

                ],
            ],

            [
                [
                    'targetEntity' => EntityInterface::class,
                ],
            ],

            [
                [
                    'targetEntity' => EntityInterface::class,
                    'fieldName' => 'foo',
                ],
            ],

            [
                [
                    'targetEntity' => EntityInterface::class,
                    'fieldName' => 'foo',
                    'type' => 1
                ],
            ],

            [
                [
                    'targetEntity' => EntityInterface::class,
                    'fieldName' => 'foo',
                    'type' => 1,
                    'isCascadePersist' => false
                ],
            ]
        ];
    }



}
