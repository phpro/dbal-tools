<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Column;

use function Psl\Dict\reindex;
use function Psl\invariant;
use function Psl\Iter\contains;
use function Psl\Vec\filter_nulls;
use function Psl\Vec\map;
use function Psl\Vec\values;

/**
 * @template-implements \IteratorAggregate<non-empty-string, Column>
 */
final readonly class Columns implements \IteratorAggregate, \Countable
{
    /**
     * @var non-empty-array<non-empty-string, Column>
     */
    public array $items;

    /**
     * @param class-string<TableColumnsInterface> $class
     */
    public static function for(string $class): self
    {
        return new self(...map($class::cases(), static fn ($case) => $case->column()));
    }

    /**
     * @param Column ...$columns
     *
     * @no-named-arguments
     */
    public function __construct(Column ...$columns)
    {
        invariant(count($columns) > 0, 'At least one column is required.');

        /** @var non-empty-array<non-empty-string, Column> */
        $this->items = reindex(
            $columns,
            /** @return non-empty-string */
            static fn (Column $column): string => $column->alias ?? $column->name,
        );
    }

    /**
     * @no-named-arguments
     */
    public function without(Column $column, Column ...$others): self
    {
        $exclude = map([$column, ...$others], static fn (Column $other) => $other->toSQL());

        return new self(...filter_nulls(
            $this->traverse(fn (Column $current): ?Column => (
                contains($exclude, $current->toSQL()) ? null : $current
            ))
        ));
    }

    /**
     * @no-named-arguments
     */
    public function with(Column $column, Column ...$others): self
    {
        return new self(...[...values($this->items), $column, ...$others]);
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public function select(): array
    {
        return map(
            $this->items,
            static fn (Column $column): string => $column->select()
        );
    }

    /**
     * @param non-empty-string|null $from
     */
    public function from(?string $from): self
    {
        return $this->map(static fn (Column $column) => $column->from($from));
    }

    /**
     * @param non-empty-string $prefix
     */
    public function prefixedAs(string $prefix): self
    {
        return $this->map(static fn (Column $column) => $column->as($prefix.'_'.$column->name));
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @param \Closure(Column): Column $mapper
     */
    public function map(\Closure $mapper): self
    {
        return new self(...map($this->items, $mapper));
    }

    /**
     * @template M
     *
     * @param \Closure(Column): M $mapper
     *
     * @return non-empty-list<M>
     */
    public function traverse(\Closure $mapper): array
    {
        return map($this->items, $mapper);
    }
}
