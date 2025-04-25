<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Column\Columns;
use function Psl\Dict\pull;

/**
 * @psalm-type JoinInfo = array{fromAlias: string, join: string, alias: string, condition: string}
 */
abstract class Table
{
    /**
     * @return non-empty-string
     */
    abstract public static function name(): string;

    abstract public static function createTable(): DoctrineTable;

    abstract public static function columns(): Columns;

    /**
     * @return array<string, Type>
     */
    public static function columnTypes(): array
    {
        return pull(
            static::createTable()->getColumns(),
            static fn (DoctrineColumn $column): Type => $column->getType(),
            static fn (DoctrineColumn $column): string => $column->getName()
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function columnType(string $column): Type
    {
        foreach (static::createTable()->getColumns() as $tableColumn) {
            if ($tableColumn->getName() === $column) {
                return $tableColumn->getType();
            }
        }

        throw new \InvalidArgumentException("Column {$column} not found.");
    }
}
