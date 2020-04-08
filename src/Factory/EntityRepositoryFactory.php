<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Factory;

use Arp\DoctrineEntityRepository\EntityRepository;
use Arp\Factory\FactoryInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Factory
 */
class EntityRepositoryFactory implements FactoryInterface
{
    /**
     * @param array $config
     *
     * @return EntityRepository|mixed
     */
    public function create(array $config = [])
    {
        $entityName     = $config['entity_name']     ?? null;
        $queryService   = $config['query_service']   ?? null;
        $persistService = $config['persist_service'] ?? null;

        return new EntityRepository(
            $entityName,
            $queryService,
            $persistService
        );
    }
}
