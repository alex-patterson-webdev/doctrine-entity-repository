<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository;

use Arp\DoctrineEntityRepository\Exception\EntityRepositoryException;
use Arp\Entity\EntityInterface;
use Doctrine\Persistence\ObjectRepository;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository
 */
interface EntityRepositoryInterface extends ObjectRepository
{
    /**
     * Save a single entity instance.
     *
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @return EntityInterface
     *
     * @throws EntityRepositoryException
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface;
}
