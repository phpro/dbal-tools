<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\GuidType;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Expression\Distinct;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DistinctTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [
            DistinctTable::class,
        ];
    }

    protected function createFixtures(): void
    {
        $this->insert(
            DistinctTable::name(),
            DistinctTable::columnTypes(),
            [
                'id' => 'f785f192-d55a-4623-8b90-bdffcfc5960c',
            ],
            [
                'id' => 'f785f192-d55a-4623-8b90-bdffcfc5960c',
            ],
        );
    }

    #[Test]
    public function it_can_distinct_search(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(DistinctTableColumns::Id->apply(static fn ($col) => new Distinct($col)))
            ->from(DistinctTable::name());

        $actualResults = $qb->fetchAllAssociative();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['id' => 'f785f192-d55a-4623-8b90-bdffcfc5960c'],
            ],
            $actualResults
        );
    }
}

class DistinctTable extends Table
{
    public static function name(): string
    {
        return 'distinct_table';
    }

    public static function columns(): Columns
    {
        return Columns::for(DistinctTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name(), [
            new DoctrineColumn(DistinctTableColumns::Id->value, new GuidType()),
        ]);
    }
}

enum DistinctTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';

    public function linkedTableClass(): string
    {
        return DistinctTable::class;
    }
}
