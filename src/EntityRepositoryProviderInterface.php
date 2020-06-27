<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Exception\EntityRepositoryNotFoundException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository
 */
interface EntityRepositoryProviderInterface
{
    /**
     * @param string $entityName
     *
     * @return bool
     */
    public function hasRepository(string $entityName): bool;

    /**
     * @param string $entityName
     * @param array  $options
     *
     * @return EntityRepositoryInterface
     *
     * @throws EntityRepositoryNotFoundException
     */
    public function getRepository(string $entityName, array $options = []): EntityRepositoryInterface;
}
