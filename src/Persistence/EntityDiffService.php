<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

use Arp\Entity\EntityInterface;
use Doctrine\ORM\UnitOfWork;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
class EntityDiffService
{
    /**
     * @param UnitOfWork      $unitOfWork
     * @param EntityInterface $entity
     *
     * @return array<mixed>
     */
    public function calculateChangeSet(UnitOfWork $unitOfWork, EntityInterface $entity): array
    {
        return $unitOfWork->getOriginalEntityData($entity);
    }
}
