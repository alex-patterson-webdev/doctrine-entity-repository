<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Constant;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Constant
 */
final class EntityEventName
{
    public const CREATE = 'create';
    public const CREATE_ERROR = 'create.error';

    public const UPDATE = 'update';
    public const UPDATE_ERROR = 'update.error';

    public const DELETE = 'delete';
    public const DELETE_ERROR = 'delete.error';

    public const SAVE_COLLECTION = 'save.collection';
    public const SAVE_COLLECTION_ERROR = 'save.collection.error';

    public const DELETE_COLLECTION = 'delete.collection';
    public const DELETE_COLLECTION_ERROR = 'delete.collection.error';
}
