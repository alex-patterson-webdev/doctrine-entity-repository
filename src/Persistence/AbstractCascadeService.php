<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\DoctrineEntityRepository\EntityRepositoryInterface;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;
use Arp\Entity\EntityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
abstract class AbstractCascadeService
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var array
     */
    protected array $options;

    /**
     * @var array
     */
    protected array $collectionOptions;

    /**
     * @param LoggerInterface $logger
     * @param array           $options
     * @param array           $collectionOptions
     */
    public function __construct(LoggerInterface $logger, array $options = [], array $collectionOptions = [])
    {
        $this->logger = $logger;
        $this->options = $options;
        $this->collectionOptions = $collectionOptions;
    }

    /**
     * @todo We should implement a way to decorate the call the getRepository() with a concrete implementation
     *       of the EntityRepositoryProviderInterface
     *
     * @param EntityManagerInterface $entityManager
     * @param string                 $entityName
     *
     * @return EntityRepositoryInterface
     * @throws PersistenceException
     */
    protected function getTargetRepository(
        EntityManagerInterface $entityManager,
        string $entityName
    ): EntityRepositoryInterface {
        /** @var EntityRepositoryInterface $targetRepository */
        try {
            $targetRepository = $entityManager->getRepository($entityName);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'Unable to perform cascade operation for entity of type \'%s\': %s',
                $entityName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        if (null === $targetRepository) {
            $errorMessage = sprintf(
                'Unable to perform cascade operation for entity of type \'%s\': '
                . 'The entity repository could not be found',
                $entityName
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        return $targetRepository;
    }

    /**
     * @param EntityInterface $sourceEntity
     * @param string          $fieldName
     * @param ClassMetadata   $sourceMetadata
     * @param ClassMetadata   $targetMetadata
     *
     * @return EntityInterface|EntityInterface[]|iterable
     *
     * @throws PersistenceException
     */
    protected function resolveTargetEntityOrCollection(
        EntityInterface $sourceEntity,
        string $fieldName,
        ClassMetadata $sourceMetadata,
        ClassMetadata $targetMetadata
    ) {
        $methodName = 'get' . ucfirst($fieldName);

        if (!method_exists($sourceEntity, $methodName)) {
            $errorMessage = sprintf(
                'Failed to find required entity method \'%s::%s\'. The method is required for cascade operations '
                . 'of field \'%s\' of target entity \'%s\'',
                $sourceMetadata->getName(),
                $methodName,
                $fieldName,
                $targetMetadata->getName()
            );

            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage);
        }

        try {
            $targetEntityOrCollection = $sourceEntity->{$methodName}();
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'The call to resolve entity of type \'%s\' from method call \'%s::%s\' failed: %s',
                $targetMetadata->getName(),
                $sourceMetadata->getName(),
                $methodName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }

        return $targetEntityOrCollection;
    }

    /**
     * @param EntityInterface|EntityInterface[]|iterable $entityOrCollection
     * @param array                                      $mapping
     *
     * @return bool
     */
    protected function isValidAssociation($entityOrCollection, array $mapping): bool
    {
        if (null === $entityOrCollection) {
            /**
             * @todo mapping class has a methods to fetch the id field mapping directly
             *
             * Note that we are hard coding the '0' key as the single field the we use as the id/primary key.
             * If we implement EntityInterface correctly we will never have a composite key.
             */
            return isset($mapping['joinColumns'][0]['nullable'])
                ? (bool)$mapping['joinColumns'][0]['nullable']
                : false;
        }

        return (is_callable($entityOrCollection) || $entityOrCollection instanceof EntityInterface);
    }

    /**
     * @param string                 $entityName
     * @param EntityManagerInterface $entityManager
     *
     * @return ClassMetadata
     *
     * @throws PersistenceException
     */
    protected function getClassMetadata(string $entityName, EntityManagerInterface $entityManager): ClassMetadata
    {
        try {
            return $entityManager->getClassMetadata($entityName);
        } catch (\Throwable $e) {
            $errorMessage = sprintf(
                'The entity metadata mapping for class \'%s\' could not be loaded: %s',
                $entityName,
                $e->getMessage()
            );
            $this->logger->error($errorMessage);

            throw new PersistenceException($errorMessage, $e->getCode(), $e);
        }
    }
}
