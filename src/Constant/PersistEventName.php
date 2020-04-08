<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Constant;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Constant
 */
final class PersistEventName
{
    public const INSERT = 'persist';
    public const INSERT_ERROR = 'persist.error';

    public const UPDATE = 'update';
    public const UPDATE_ERROR = 'update.error';

    public const DELETE = 'delete';
    public const DELETE_ERROR = 'delete.error';

    public const FLUSH = 'flush';
    public const FLUSH_ERROR = 'flush.error';
}
