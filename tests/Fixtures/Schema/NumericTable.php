<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Schema;

use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Schema\Table;
use PhproTest\DbalTools\Fixtures\Type\NumericIdType;

/**
 * @psalm-import-type JoinInfo from Table
 */
final class NumericTable extends Table
{
    public static function name(): string
    {
        return 'numerics';
    }

    public static function columns(): Columns
    {
        return Columns::for(NumericTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        $table = new DoctrineTable(self::name());
        $table->addColumn(NumericTableColumns::SmallInt->value, Types::SMALLINT);
        $table->addColumn(NumericTableColumns::Integer->value, Types::INTEGER);
        $table->addColumn(NumericTableColumns::BigInt->value, Types::BIGINT);
        $table->addColumn(NumericTableColumns::NumericId->value, NumericIdType::NAME);

        return $table;
    }
}
