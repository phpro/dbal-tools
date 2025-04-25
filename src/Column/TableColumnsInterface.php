<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Column;

use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Schema\Table;

interface TableColumnsInterface extends /* String */ \BackedEnum, Expression
{
    /**
     * @return class-string<Table>
     */
    public function linkedTableClass(): string;

    public function column(): Column;

    public function columnType(): Type;

    /**
     * @param non-empty-string|null $from
     */
    public function onTable(?string $from): Column;

    /**
     * @param non-empty-string|null $alias
     */
    public function as(?string $alias): Column;

    /**
     * @return non-empty-string
     */
    public function select(): string;

    /**
     * @return non-empty-string
     */
    public function use(): string;

    /**
     * @return non-empty-string
     */
    public function toSQL(): string;

    /**
     * @param (\Closure(Column): (Expression|non-empty-string)) $action
     * @param non-empty-string|null                             $as
     *
     * @return non-empty-string
     */
    public function apply(\Closure $action, ?string $as = null): string;
}
