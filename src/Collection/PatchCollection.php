<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Collection;

use function Psl\Iter\contains;
use function Psl\Vec\map;

final readonly class PatchCollection
{
    /**
     * This method can be used to patch a collection of objects based on a previous version of the collection.
     * It will know to insert, update or delete each object based on the provided new and old list.
     *
     * @template T
     * @template I
     *
     * @param iterable<T>       $newCollection
     * @param iterable<T>       $previousCollection
     * @param \Closure(T): I    $idProvider
     * @param \Closure(T): void $insert
     * @param \Closure(T): void $update
     * @param \Closure(T): void $delete
     */
    public function patch(
        iterable $newCollection,
        iterable $previousCollection,
        \Closure $idProvider,
        \Closure $insert,
        \Closure $update,
        \Closure $delete,
    ): void {
        $previousIds = map($previousCollection, $idProvider);
        $newIds = map($newCollection, $idProvider);
        $toDelete = array_diff($previousIds, $newIds);
        $toInsert = array_diff($newIds, $previousIds);
        $toUpdate = array_intersect($newIds, $previousIds);

        foreach ($newCollection as $record) {
            $recordId = $idProvider($record);
            match (true) {
                contains($toInsert, $recordId) => $insert($record),
                contains($toUpdate, $recordId) => $update($record),
            };
        }

        foreach ($previousCollection as $record) {
            if (contains($toDelete, $idProvider($record))) {
                $delete($record);
            }
        }
    }
}
