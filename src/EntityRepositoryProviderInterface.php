<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

use Psr\Container\ContainerInterface;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository
 */
interface EntityRepositoryProviderInterface extends ContainerInterface
{
    /**
     * @param string $entityName
     *
     * @return EntityRepositoryInterface|null
     */
    public function getEntityRepository(string $entityName): ?EntityRepositoryInterface;
}
