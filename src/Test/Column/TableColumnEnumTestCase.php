<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\Column;

use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
        if (!array_key_exists(0, $this->className()::cases())) {
            self::markTestSkipped('No cases found in TableColumn enum');
        }

        $linkedTableClass = $this->className()::cases()[0]->linkedTableClass();
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
