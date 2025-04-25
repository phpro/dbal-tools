<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\StringType;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Expression\Expressions;
use Phpro\DbalTools\Expression\Search;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class SearchTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [
            SearchTable::class,
        ];
    }

    protected function createFixtures(): void
    {
        $this->insert(
            SearchTable::name(),
            SearchTable::columnTypes(),
            [
                'id' => '2183c064-ad4c-44e1-94c1-48f5f86d0720',
                'first_name' => 'Fred',
                'last_name' => 'Flintstone',
            ],
            [
                'id' => '720c1ae9-61bd-44fd-babf-4519912b9ba8',
                'first_name' => 'Barnie',
                'last_name' => 'Flintstone',
            ],
            [
                'id' => 'a91b4b25-5567-43bd-9e9b-461fadcd6ddc',
                'first_name' => 'Flint',
                'last_name' => 'Flintstone',
            ],
            [
                'id' => '672a61cc-2bad-4b3c-8cf5-5a5c54891274',
                'first_name' => 'Alfred',
                'last_name' => 'Firestone',
            ],
            [
                'id' => '12addfcb-b642-4d4c-8652-6a9c00481248',
                'first_name' => 'Jean Paul',
                'last_name' => 'Firestone',
            ]
        );
    }

    #[Test]
    #[DataProvider('provideSearchCases')]
    public function it_can_search_records(string $query, array $expectedResults): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('id')
            ->from(SearchTable::name())
            ->where(
                (new Search(
                    new Expressions(
                        SearchTableColumns::FirstName->column(),
                        SearchTableColumns::LastName->column()
                    ),
                    $query,
                    Search::queryBuilderPlaceholderValueProvider($qb)
                ))->toSQL()
            );

        $actualResults = $qb->fetchAllAssociative();

        self::assertSame($expectedResults, $actualResults);
    }

    public static function provideSearchCases(): iterable
    {
        yield 'search-by-first-name' => [
            'Fred',
            [
                ['id' => '2183c064-ad4c-44e1-94c1-48f5f86d0720'],
                ['id' => '672a61cc-2bad-4b3c-8cf5-5a5c54891274'],
            ],
        ];
        yield 'search-by-last-name' => [
            'Fire',
            [
                ['id' => '672a61cc-2bad-4b3c-8cf5-5a5c54891274'],
                ['id' => '12addfcb-b642-4d4c-8652-6a9c00481248'],
            ],
        ];
        yield 'search-by-first-and-name' => [
            'Flint',
            [
                ['id' => '2183c064-ad4c-44e1-94c1-48f5f86d0720'],
                ['id' => '720c1ae9-61bd-44fd-babf-4519912b9ba8'],
                ['id' => 'a91b4b25-5567-43bd-9e9b-461fadcd6ddc'],
            ],
        ];
        yield 'space-separated' => [
            'Jean Paul',
            [
                ['id' => '12addfcb-b642-4d4c-8652-6a9c00481248'],
            ],
        ];
        yield 'SCREAM' => [
            'JEAN',
            [
                ['id' => '12addfcb-b642-4d4c-8652-6a9c00481248'],
            ],
        ];
    }
}

class SearchTable extends Table
{
    public static function name(): string
    {
        return 'search_expression';
    }

    public static function columns(): Columns
    {
        return Columns::for(SearchTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name(), [
            new DoctrineColumn(SearchTableColumns::Id->value, new GuidType()),
            new DoctrineColumn(SearchTableColumns::FirstName->value, new StringType()),
            new DoctrineColumn(SearchTableColumns::LastName->value, new StringType()),
        ]);
    }
}

enum SearchTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';
    case FirstName = 'first_name';
    case LastName = 'last_name';

    public function linkedTableClass(): string
    {
        return SearchTable::class;
    }
}
