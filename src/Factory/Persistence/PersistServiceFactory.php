<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Factory\Persistence;

use Arp\DoctrineEntityRepository\Persistence\PersistService;
use Arp\DoctrineEntityRepository\Persistence\PersistServiceInterface;
use Arp\EventDispatcher\EventDispatcher;
use Arp\Factory\Exception\FactoryException;
use Arp\Factory\FactoryInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Factory\Persistence
 */
final class PersistServiceFactory implements FactoryInterface
{
    /**
     * @var string
     */
    private $defaultClassName = PersistService::class;

    /**
     * Create an object of type PersistServiceInterface based on the provided $config options.
     *
     * @param array $config
     *
     * @return PersistServiceInterface
     *
     * @throws FactoryException
     */
    public function create(array $config = []): PersistServiceInterface
    {
        $className = $config['class_name'] ?? $this->defaultClassName;

        if (! is_a($className, PersistServiceInterface::class, true)) {
            throw new FactoryException(
                sprintf(
                    'The \'class_name\' configuration option must reference a class of type  \'%s\'',
                    PersistServiceInterface::class
                )
            );
        }

        $entityName = $config['entity_name'] ?? null;

        if (null === $entityName) {
            throw new FactoryException(
                sprintf('The required \'entity_manager\' configuration option is required in \'%s\'', static::class)
            );
        }

        $entityManager = $config['entity_manager'] ?? null;

        if (null === $entityManager) {
            throw new FactoryException(
                sprintf('The required \'entity_manager\' configuration option is required in \'%s\'', static::class)
            );
        }

        if (! $entityManager instanceof EntityManagerInterface) {
            throw new FactoryException(
                sprintf(
                    'The \'entity_manager\' must be an object of type \'%s\'; \'%s\' provided in \'%s\'',
                    EntityManagerInterface::class,
                    (is_object($entityManager) ? get_class($entityManager) : gettype($entityManager)),
                    static::class
                )
            );
        }

        try {
            $classMetadata = $entityManager->getClassMetadata($className);
        } catch (\Throwable $e) {
            throw new FactoryException(
                sprintf(
                    'The class metadata for entity \'%s\' could not be loaded in \'%s\'',
                    $className,
                    static::class
                )
            );
        }

        $eventDispatcher = new EventDispatcher();



        return new PersistService($classMetadata, $eventDispatcher, $logger);
    }
}
