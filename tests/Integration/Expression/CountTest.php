<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\GuidType;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Expression\Count;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class CountTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [
            CountTable::class,
        ];
    }

    protected function createFixtures(): void
    {
        $this->insert(
            CountTable::name(),
            CountTable::columnTypes(),
            [
                'id' => '3bbeb63a-c925-4796-a68f-8b4ab451f128',
            ],
            [
                'id' => 'd2ec80fc-07bc-4117-af07-750ea3c67092',
            ],
        );
    }

    #[Test]
    public function it_can_count_regular(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(CountTableColumns::Id->apply(static fn ($col) => new Count($col), 'count'))
            ->from(CountTable::name());

        $actualResults = $qb->fetchAssociative();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(2, $actualResults['count']);
    }
}

class CountTable extends Table
{
    public static function name(): string
    {
        return 'count_expression';
    }

    public static function columns(): Columns
    {
        return Columns::for(CountTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name(), [
            new DoctrineColumn(CountTableColumns::Id->value, new GuidType()),
        ]);
    }
}

enum CountTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';

    public function linkedTableClass(): string
    {
        return CountTable::class;
    }
}
