<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Column;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Expression;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColumnTest extends TestCase
{
    #[Test]
    public function it_works_on_name_only(): void
    {
        $column = new Column('name', null, null);
        self::assertSame('name', $column->name);
        self::assertNull($column->from);
        self::assertNull($column->alias);
        self::assertSame('name', $column->select());
        self::assertSame('name', $column->use());
        self::assertSame('name', $column->toSQL());
    }

    #[Test]
    public function it_works_on_from_and_name(): void
    {
        $column = new Column('name', 'table', null);
        self::assertSame('name', $column->name);
        self::assertSame('table', $column->from);
        self::assertNull($column->alias);
        self::assertSame('table.name', $column->select());
        self::assertSame('table.name', $column->use());
        self::assertSame('table.name', $column->toSQL());
    }

    #[Test]
    public function it_works_on_from_and_name_with_alias(): void
    {
        $column = new Column('name', 'table', 'alias');
        self::assertSame('name', $column->name);
        self::assertSame('table', $column->from);
        self::assertSame('alias', $column->alias);
        self::assertSame('table.name AS alias', $column->select());
        self::assertSame('alias', $column->use());
        self::assertSame('table.name', $column->toSQL());
    }

    #[Test]
    public function it_can_fluently_change_settings(): void
    {
        $column = new Column('field', null, null);
        $actual = $column->from('table')->as('alias');

        self::assertNotSame($column, $actual);
        self::assertSame('field', $actual->name);
        self::assertSame('table', $actual->from);
        self::assertSame('alias', $actual->alias);
    }

    #[Test]
    public function it_is_an_expression(): void
    {
        $column = new Column('field', 'table', 'alias');
        self::assertInstanceOf(Expression::class, $column);
    }

    #[Test]
    public function it_can_apply_column_action(): void
    {
        $column = new Column('field', 'table');
        $action = static fn (Column $column) => new class($column) implements Expression {
            public function __construct(private Expression $expression)
            {
            }

            public function toSQL(): string
            {
                return 'action('.$this->expression->toSQL().')';
            }
        };

        self::assertSame('action(table.field)', $column->apply($action));
        self::assertSame('action(table.field) AS result', $column->apply($action, 'result'));
        self::assertSame('foo(table.field) AS result', $column->apply(fn (Column $col) => 'foo('.$col->toSQL().')', 'result'));
    }
}
