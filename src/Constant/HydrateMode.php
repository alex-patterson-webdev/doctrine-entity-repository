<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Constant;

use Doctrine\ORM\Query;

/**
 * Enum of the possible Doctrine query hydration types.
 *
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Constant
 */
final class HydrateMode
{
    public const ARRAY = Query::HYDRATE_ARRAY;
    public const OBJECT = Query::HYDRATE_OBJECT;
    public const SCALAR = Query::HYDRATE_SCALAR;
    public const SIMPLE_OBJECT = Query::HYDRATE_SIMPLEOBJECT;
    public const SINGLE_SCALAR = Query::HYDRATE_SINGLE_SCALAR;
}
