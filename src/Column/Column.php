<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Column;

use Phpro\DbalTools\Expression\Expression;

final readonly class Column implements Expression
{
    /**
     * @param non-empty-string      $name
     * @param non-empty-string|null $from
     * @param non-empty-string|null $alias
     */
    public function __construct(
        public string $name,
        public ?string $from,
        public ?string $alias = null,
    ) {
    }

    /**
     * @param non-empty-string|null $from
     */
    public function from(?string $from): self
    {
        return new self($this->name, $from, $this->alias);
    }

    /**
     * @param non-empty-string|null $alias
     */
    public function as(?string $alias): self
    {
        return new self($this->name, $this->from, $alias);
    }

    /**
     * An expression will use the fully qualified name of the column. E.g. table.column.
     *
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        $qualifiedName = $this->name;
        if (null !== $this->from) {
            $qualifiedName = $this->from.'.'.$qualifiedName;
        }

        return $qualifiedName;
    }

    /**
     * @return non-empty-string
     */
    public function select(): string
    {
        $select = $this->toSQL();

        if (null !== $this->alias) {
            $select .= ' AS '.$this->alias;
        }

        return $select;
    }

    /**
     * @return non-empty-string
     */
    public function use(): string
    {
        if (null !== $this->alias) {
            return $this->alias;
        }

        return $this->toSQL();
    }

    /**
     * @param (\Closure(Column): (Expression|non-empty-string)) $action
     * @param non-empty-string|null                             $as
     *
     * @return non-empty-string
     */
    public function apply(\Closure $action, ?string $as = null): string
    {
        $applied = $action($this);

        return sprintf(
            '%s%s',
            $applied instanceof Expression ? $applied->toSQL() : $applied,
            null !== $as ? ' AS '.$as : ''
        );
    }
}
