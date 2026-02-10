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

    #[Test]
    public function it_can_add_columns_with(): void
    {
        $columns = Columns::for(TableColumnsImplementation::class);
        $newColumn = new Column('extra', 'table');
        $result = $columns->with($newColumn);

        self::assertCount(3, $result);
        self::assertSame(['id', 'name', 'extra'], array_keys(iterator_to_array($result)));
        self::assertSame($newColumn, $result->items['extra']);

        // Adding multiple columns
        $colA = new Column('a', 'table');
        $colB = new Column('b', 'table');
        $result2 = $columns->with($colA, $colB);
        self::assertCount(4, $result2);
        self::assertSame(['id', 'name', 'a', 'b'], array_keys(iterator_to_array($result2)));
    }

    #[Test]
    public function it_can_remove_columns_without(): void
    {
        $columns = Columns::for(TableColumnsImplementation::class);
        $idColumn = new Column('id', 'table');
        $nameColumn = new Column('name', 'table');
        $emailColumn = new Column('email', 'table');

        // Remove one column
        $result = $columns->without($idColumn, $emailColumn);
        self::assertCount(1, $result);
        self::assertArrayHasKey('name', $result->items);

        // Remove multiple comments at once:
        $result = $columns->with($emailColumn)->without($idColumn, $nameColumn);
        self::assertCount(1, $result);
        self::assertArrayHasKey('email', $result->items);

        // Remove a column not present (should not change)
        $notPresent = new Column('not_present', 'table');
        $result2 = $columns->without($notPresent);
        self::assertCount(2, $result2);
        self::assertSame(['id', 'name'], array_keys(iterator_to_array($result2)));

        // Remove all columns one by one, expect exception on empty
        $oneLeft = $columns->without($idColumn);
        $this->expectExceptionMessage('At least one column is required.');
        $oneLeft->without($nameColumn);
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
