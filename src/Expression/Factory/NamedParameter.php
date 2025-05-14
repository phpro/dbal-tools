<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression\Factory;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
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
        return self::createForType($queryBuilder, $column->columnType(), $value, $parameterName);
    }

    /**
     * @param Types::*|Type $type
     */
    public static function createForType(
        QueryBuilder $queryBuilder,
        string|Type $type,
        mixed $value,
        ?string $parameterName = null,
    ): SqlExpression {
        $type = $type instanceof Type ? $type : Type::getType($type);

        return SqlExpression::parameter(
            $queryBuilder->createNamedParameter($value, $type, $parameterName)
        );
    }
}
