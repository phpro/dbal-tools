<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression\Factory;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Expression\Factory\NamedParameter;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class NamedParameterTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_create_named_parameter_for_table_column(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            NamedParameter::createForTableColumn(
                $qb,
                NamedParameterTableColumns::Id,
                'hello'
            )->toSQL(),
            NamedParameter::createForTableColumn(
                $qb,
                NamedParameterTableColumns::Id,
                'world',
                ':paramName'
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], 'hello');
        self::assertSame($actualResults[1], 'world');
    }

    #[Test]
    public function it_can_create_named_parameter_for_type(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            NamedParameter::createForType(
                $qb,
                Type::getType(Types::STRING),
                'hello'
            )->toSQL(),
            NamedParameter::createForType(
                $qb,
                Types::STRING,
                'world',
                ':paramName'
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], 'hello');
        self::assertSame($actualResults[1], 'world');
    }
}

enum NamedParameterTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';

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
