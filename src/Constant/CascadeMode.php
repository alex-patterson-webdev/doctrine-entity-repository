<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Constant;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Constant
 */
final class CascadeMode
{
    public const ALL = 'all';
    public const SAVE = 'persist';
    public const DELETE = 'delete';
    public const NONE = 'none';
}
