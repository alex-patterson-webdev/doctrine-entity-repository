<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Constant;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Constant
 */
final class QueryServiceOption
{
    public const ORDER_BY = 'order_by';
    public const LIMIT = 'limit';
    public const OFFSET = 'offset';
    public const ASSOCIATION = 'association';
    public const HINTS = 'hints';
    public const LOCK_MODE = 'lock_mode';
    public const ENTITY = 'entity';
    public const FIRST_RESULT = 'first_result';
    public const MAX_RESULTS = 'max_results';
    public const DQL = 'dql';
    public const HYDRATION_MODE = 'hydration_mode';
    public const PARAMS = 'params';
}
