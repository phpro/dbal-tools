<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Column;

use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Schema\Table;

trait TableColumnsTrait
{
    /**
     * @return class-string<Table>
     */
    abstract public function linkedTableClass(): string;

    public function column(): Column
    {
        return new Column($this->value, ($this->linkedTableClass())::name());
    }

    public function columnType(): Type
    {
        return ($this->linkedTableClass())::columnType($this->value);
    }

    /**
     * @param non-empty-string|null $from
     */
    public function onTable(?string $from): Column
    {
        return $this->column()->from($from);
    }

    /**
     * @param non-empty-string|null $alias
     */
    public function as(?string $alias): Column
    {
        return $this->column()->as($alias);
    }

    /**
     * @return non-empty-string
     */
    public function select(): string
    {
        return $this->column()->select();
    }

    /**
     * @return non-empty-string
     */
    public function use(): string
    {
        return $this->column()->use();
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return $this->column()->toSQL();
    }

    /**
     * @param (\Closure(Column): (Expression|non-empty-string)) $action
     * @param non-empty-string|null                             $as
     *
     * @return non-empty-string
     */
    public function apply(\Closure $action, ?string $as = null): string
    {
        return $this->column()->apply($action, $as);
    }
}
