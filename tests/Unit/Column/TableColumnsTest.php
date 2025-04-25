<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Column;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Schema\Table;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TableColumnsTest extends TestCase
{
    #[Test]
    public function it_contains_column(): void
    {
        $column = TableColumnsDecorator::Id->column();

        self::assertEquals('id', $column->name);
        self::assertEquals('table_columns_table', $column->from);
    }

    #[Test]
    public function it_can_select_directly(): void
    {
        self::assertEquals('table_columns_table.id', TableColumnsDecorator::Id->select());
    }

    #[Test]
    public function it_can_use_directly(): void
    {
        self::assertEquals('table_columns_table.id', TableColumnsDecorator::Id->use());
    }

    #[Test]
    public function it_can_get_sql_directly(): void
    {
        self::assertInstanceOf(Expression::class, TableColumnsDecorator::Id);
        self::assertEquals('table_columns_table.id', TableColumnsDecorator::Id->toSQL());
    }

    #[Test]
    public function it_can_change_table_name_directly(): void
    {
        $column = TableColumnsDecorator::Id->onTable('another_table');

        self::assertEquals('id', $column->name);
        self::assertEquals('another_table', $column->from);
    }

    #[Test]
    public function it_can_change_alias_directly(): void
    {
        $column = TableColumnsDecorator::Id->as('alias');

        self::assertEquals('id', $column->name);
        self::assertEquals('table_columns_table', $column->from);
        self::assertEquals('alias', $column->alias);
    }

    #[Test]
    public function it_can_apply_action_directly(): void
    {
        $action = static fn (Column $column) => new class($column) implements Expression {
            public function __construct(private Expression $expression)
            {
            }

            public function toSQL(): string
            {
                return 'action('.$this->expression->toSQL().')';
            }
        };

        self::assertSame('action(table_columns_table.id)', TableColumnsDecorator::Id->apply($action));
        self::assertSame('action(table_columns_table.id) AS alias', TableColumnsDecorator::Id->apply($action, 'alias'));
    }

    #[Test]
    public function it_can_fetch_column_type(): void
    {
        self::assertEquals(Type::getType(Types::STRING), TableColumnsDecorator::Id->columnType());
        self::assertEquals(Type::getType(Types::STRING), TableColumnsDecorator::Name->columnType());
    }

    #[Test]
    public function it_can_fail_fetching_unknown_column_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Column unknown not found.');

        TableColumnsDecorator::Unknown->columnType();
    }
}

final class TableColumnsTable extends Table
{
    public static function name(): string
    {
        return 'table_columns_table';
    }

    public static function createTable(): DoctrineTable
    {
        $table = new DoctrineTable(self::name());

        $table->addColumn(TableColumnsDecorator::Id->value, Types::STRING);
        $table->addColumn(TableColumnsDecorator::Name->value, Types::STRING);

        return $table;
    }

    public static function columns(): Columns
    {
        return Columns::for(__CLASS__);
    }
}

enum TableColumnsDecorator: string implements TableColumnsInterface
{
    case Id = 'id';
    case Name = 'name';
    case Unknown = 'unknown';

    use TableColumnsTrait;

    public function linkedTableClass(): string
    {
        return TableColumnsTable::class;
    }
}
