<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence
 */
interface TransactionServiceInterface
{
    /**
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * @return void
     */
    public function commitTransaction(): void;

    /**
     * @return void
     */
    public function rollbackTransaction(): void;
}
