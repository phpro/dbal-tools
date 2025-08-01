<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pager;

/**
 * @template T
 *
 * @template-extends \IteratorAggregate<int, T>
 */
interface Pager extends \IteratorAggregate
{
    public function pagination(): Pagination;

    public function totalResults(): int;

    public function totalPages(): int;

    /**
     * @template O
     *
     * @param \Closure(T): O $mapper
     *
     * @return iterable<int, O>
     */
    public function traverse(\Closure $mapper): iterable;
}
