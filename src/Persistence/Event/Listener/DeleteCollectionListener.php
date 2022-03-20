<?php

declare(strict_types=1);

namespace Arp\DoctrineEntityRepository\Persistence\Event\Listener;

use Arp\DoctrineEntityRepository\Constant\ClearMode;
use Arp\DoctrineEntityRepository\Constant\DeleteMode;
use Arp\DoctrineEntityRepository\Constant\EntityEventOption;
use Arp\DoctrineEntityRepository\Constant\FlushMode;
use Arp\DoctrineEntityRepository\Constant\PersistMode;
use Arp\DoctrineEntityRepository\Constant\TransactionMode;
use Arp\DoctrineEntityRepository\Persistence\Event\CollectionEvent;
use Arp\DoctrineEntityRepository\Persistence\Exception\PersistenceException;

/**
 * @author  Alex Patterson <alex.patterson.webdev@gmail.com>
 * @package Arp\DoctrineEntityRepository\Persistence\Event\Listener
 */
final class DeleteCollectionListener
{
    /**
     * @var array<mixed>
     */
    private array $defaultOptions = [
        EntityEventOption::FLUSH_MODE       => FlushMode::DISABLED,
        EntityEventOption::TRANSACTION_MODE => TransactionMode::DISABLED,
        EntityEventOption::CLEAR_MODE       => ClearMode::DISABLED,
        EntityEventOption::PERSIST_MODE     => PersistMode::ENABLED,
    ];

    /**
     * @param array<mixed> $defaultOptions
     */
    public function __construct(array $defaultOptions = [])
    {
        $this->defaultOptions = empty($defaultOptions) ? $this->defaultOptions : $defaultOptions;
    }

    /**
     * Perform a save of the managed collection
     *
     * @param CollectionEvent $event
     *
     * @throws PersistenceException
     */
    public function __invoke(CollectionEvent $event): void
    {
        $collection = $event->getCollection();
        if (empty($collection)) {
            return;
        }

        $event->getLogger()->debug(sprintf('Deleting \'%s\' collection', $event->getEntityName()));

        $saveOptions = array_replace_recursive(
            $this->defaultOptions,
            $event->getParam(EntityEventOption::COLLECTION_DELETE_OPTIONS, [])
        );

        $deletedCount = 0;
        $persistService = $event->getPersistService();
        foreach ($collection as &$entity) {
            if (true === $persistService->delete($entity, $saveOptions)) {
                $deletedCount++;
            }
        }
        unset($entity);

        $event->getParameters()->setParam('deleted_count', $deletedCount);
    }
}
