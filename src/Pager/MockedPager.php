<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pager;

use function Psl\Math\ceil;
use function Psl\Vec\map;

/**
 * @template T
 *
 * @template-implements Pager<T>
 */
final readonly class MockedPager implements Pager
{
    /**
     * @param array<int, T> $records
     */
    public function __construct(
        private Pagination $pagination,
        private array $records,
    ) {
    }

    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    public function totalResults(): int
    {
        return count($this->records);
    }

    public function totalPages(): int
    {
        $totalResults = $this->totalResults();
        $limit = $this->pagination()->limit;
        if (!$totalResults) {
            return 1;
        }

        return (int) ceil($this->totalResults() / $limit);
    }

    public function traverse(\Closure $mapper): iterable
    {
        return map($this, $mapper);
    }

    public function getIterator(): \Traversable
    {
        yield from array_slice(
            $this->records,
            ($this->pagination()->page - 1) * $this->pagination->limit,
            $this->pagination()->limit
        );
    }
}
