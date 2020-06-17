<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository
 */
interface EntityRepositoryProviderInterface
{
    /**
     * @param string $entityName
     *
     * @return EntityRepositoryInterface|null
     */
    public function getEntityRepository(string $entityName): ?EntityRepositoryInterface;
}
