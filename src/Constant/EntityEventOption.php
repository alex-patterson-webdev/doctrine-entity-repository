<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Constant;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Constant
 */
final class EntityEventOption
{
    public const FLUSH_MODE = 'flush_mode';
    public const CLEAR_MODE = 'clear_mode';
    public const PERSIST_MODE = 'persist_mode';
    public const DELETE_MODE = 'delete_mode';
}
