<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression\Factory;

use Doctrine\DBAL\Query\QueryBuilder;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Expression\SqlExpression;

final class NamedParameter
{
    public static function createForTableColumn(
        QueryBuilder $queryBuilder,
        TableColumnsInterface $column,
        mixed $value,
        ?string $parameterName = null,
    ): SqlExpression {
        return SqlExpression::parameter(
            $queryBuilder->createNamedParameter($value, $column->columnType(), $parameterName)
        );
    }
}
