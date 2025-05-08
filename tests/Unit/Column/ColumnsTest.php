<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Column;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Schema\Table;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColumnsTest extends TestCase
{
    #[Test]
    public function it_throws_on_empty_columns(): void
    {
        $this->expectExceptionMessage('At least one column is required.');
        new Columns();
    }

    #[Test]
    public function it_contains_multiple_columns(): void
    {
        $columns = new Columns(
            $column1 = new Column('id', 'table'),
            $column2 = new Column('name', 'table'),
        );

        self::assertCount(2, $columns);
        self::assertSame(['id' => $column1, 'name' => $column2], iterator_to_array($columns));
    }

    #[Test]
    public function it_can_construct_from_table_columns_interface(): void
    {
        $columns = Columns::for(TableColumnsImplementation::class);

        self::assertCount(2, $columns);
        self::assertSame(['table.id', 'table.name'], $columns->select());
    }

    #[Test]
    public function it_can_change_table_name(): void
    {
        $columns = Columns::for(TableColumnsImplementation::class)->from('another_table');

        self::assertSame(['another_table.id', 'another_table.name'], $columns->select());
    }

    #[Test]
    public function it_can_prefix_field_names(): void
    {
        $columns = Columns::for(TableColumnsImplementation::class)->prefixedAs('prefix');

        self::assertSame(['table.id AS prefix_id', 'table.name AS prefix_name'], $columns->select());
    }

    #[Test]
    public function it_can_map_columns(): void
    {
        $columns = Columns::for(TableColumnsImplementation::class);
        $actual = $columns->map(static fn (Column $column): Column => $column->from('x'));

        self::assertNotSame($columns, $actual);
        self::assertSame(['x.id', 'x.name'], $actual->select());
    }

    #[Test]
    public function it_can_traverse_columns(): void
    {
        $columns = Columns::for(TableColumnsImplementation::class);
        $actual = $columns->traverse(static fn (Column $column): string => $column->name);

        self::assertSame(['id', 'name'], $actual);
    }
}

enum TableColumnsImplementation: string implements TableColumnsInterface
{
    case Id = 'id';
    case Name = 'name';

    use TableColumnsTrait;

    public function column(): Column
    {
        return new Column($this->value, 'table');
    }

    public function columnType(): Type
    {
        return Type::getType(Types::STRING);
    }

    public function linkedTableClass(): string
    {
        return Table::class;
    }
}
