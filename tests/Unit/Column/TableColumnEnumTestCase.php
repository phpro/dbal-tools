<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Column;

use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Schema\Table;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function Psl\Type\class_string;
use function Psl\Type\string;
use function Psl\Vec\map;

abstract class TableColumnEnumTestCase extends TestCase
{
    /**
     * @return class-string<TableColumnsInterface>
     */
    abstract public function className(): string;

    #[Test]
    public function it_knows_how_to_build_column(): void
    {
        foreach ($this->className()::cases() as $column) {
            self::assertSame($column->column()->name, $column->value);
        }
    }

    #[Test]
    public function it_knows_how_to_build_column_types_for_every_column(): void
    {
        foreach ($this->className()::cases() as $column) {
            self::assertInstanceOf(Type::class, $column->columnType());
        }
    }

    #[Test]
    public function it_maps_to_table_columns(): void
    {
        $linkedTableClass = class_string(Table::class)->assert($this->className()::cases()[0]?->linkedTableClass());
        $columns = $linkedTableClass::columns();

        self::assertEquals(
            new Columns(...map(
                $this->className()::cases(),
                static fn ($column) => $column->column()
            )),
            $columns
        );
    }
}
