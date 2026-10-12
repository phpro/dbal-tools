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
            static fn (DoctrineColumn $column): Type => self::typeOf($column),
            static fn (DoctrineColumn $column): string => $column->getObjectName()->toString()
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function columnType(string $column): Type
    {
        foreach (static::createTable()->getColumns() as $tableColumn) {
            if ($tableColumn->getObjectName()->toString() === $column) {
                return self::typeOf($tableColumn);
            }
        }

        throw new \InvalidArgumentException("Column {$column} not found.");
    }

    /**
     * DBAL 4.5 deprecates Column::getType() in favour of getTypeName(), which does not exist before 4.5.
     *
     * Each DBAL version only runs one branch, so this stays on one line to keep line coverage at 100%.
     *
     * @psalm-suppress DeprecatedMethod - Only deprecated from DBAL 4.5, where the getTypeName() branch is taken.
     * @psalm-suppress MixedArgument - Before DBAL 4.5 getTypeName() is unknown, so its result is mixed.
     * @psalm-suppress UnusedPsalmSuppress - Each suppression only applies to one DBAL version.
     */
    private static function typeOf(DoctrineColumn $column): Type
    {
        return method_exists($column, 'getTypeName') ? Type::getType($column->getTypeName()) : $column->getType();
    }
}
