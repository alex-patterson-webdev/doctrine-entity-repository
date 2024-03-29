<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Constant;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Constant
 */
final class EntityEventOption
{
    public const CASCADE_MODE = 'cascade_mode';
    public const CASCADE_SAVE_OPTIONS = 'cascade_save_options';
    public const CASCADE_SAVE_COLLECTION_OPTIONS = 'cascade_save_collection_options';
    public const CASCADE_DELETE_OPTIONS = 'cascade_delete_options';
    public const CASCADE_DELETE_COLLECTION_OPTIONS = 'cascade_delete_collection_options';
    public const CLEAR_MODE = 'clear_mode';
    public const COLLECTION_DELETE_OPTIONS = 'collection_delete_options';
    public const COLLECTION_SAVE_OPTIONS = 'collection_save_options';
    public const DATE_CREATED_MODE = 'date_created_mode';
    public const DATE_UPDATED_MODE = 'date_updated_mode';
    public const DATE_DELETED_MODE = 'date_deleted_mode';
    public const DELETE_MODE = 'delete_mode';
    public const FLUSH_MODE = 'flush_mode';
    public const LOG_ERRORS = 'log_errors';
    public const PERSIST_MODE = 'persist_mode';
    public const THROW_EXCEPTIONS = 'throw_exceptions';
    public const TRANSACTION_MODE = 'transaction_mode';
}
