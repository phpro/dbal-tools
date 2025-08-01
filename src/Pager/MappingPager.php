<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pager;

use function Psl\Vec\map;

/**
 * @template I
 * @template O
 *
 * @implements Pager<O>
 */
final readonly class MappingPager implements Pager
{
    /**
     * @param Pager<I>       $pager
     * @param \Closure(I): O $mapper
     */
    public function __construct(
        private Pager $pager,
        private \Closure $mapper,
    ) {
    }

    public function pagination(): Pagination
    {
        return $this->pager->pagination();
    }

    public function totalResults(): int
    {
        return $this->pager->totalResults();
    }

    public function totalPages(): int
    {
        return $this->pager->totalPages();
    }

    public function traverse(\Closure $mapper): iterable
    {
        return map($this, $mapper);
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->pager as $value) {
            yield ($this->mapper)($value);
        }
    }
}
