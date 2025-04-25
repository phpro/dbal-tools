<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\GuidType;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Expression\JsonbAggStrict;
use Phpro\DbalTools\Expression\JsonbBuildObject;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Json\decode;

final class JsonbAggStrictTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [
            JsonAggStrictTable::class,
        ];
    }

    protected function createFixtures(): void
    {
        $this->insert(
            JsonAggStrictTable::name(),
            JsonAggStrictTable::columnTypes(),
            [
                'id' => '3bbeb63a-c925-4796-a68f-8b4ab451f128',
            ],
            [
                'id' => 'd2ec80fc-07bc-4117-af07-750ea3c67092',
            ],
        );
    }

    #[Test]
    public function it_can_apply_json_strict(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(JsonAggStrictTableColumns::Id->apply(static fn ($col) => new JsonbAggStrict($col), 'ids'))
            ->from(JsonAggStrictTable::name());

        $actualResults = $qb->fetchAssociative();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                '3bbeb63a-c925-4796-a68f-8b4ab451f128',
                'd2ec80fc-07bc-4117-af07-750ea3c67092',
            ],
            decode($actualResults['ids'])
        );
    }

    #[Test]
    public function it_can_apply_json_strict_ordered_by(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            JsonAggStrictTableColumns::Id->apply(static fn ($col) => new JsonbAggStrict(
                $col,
                orderBy: new OrderBy(OrderBy::field(JsonAggStrictTableColumns::Id, OrderBy::DESC))
            ), 'ids')
        )->from(JsonAggStrictTable::name());

        $actualResults = $qb->fetchAssociative();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                'd2ec80fc-07bc-4117-af07-750ea3c67092',
                '3bbeb63a-c925-4796-a68f-8b4ab451f128',
            ],
            decode($actualResults['ids'])
        );
    }

    #[Test]
    public function it_can_apply_json_strict_on_many_left_joined_json_objects(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb
            ->select(
                JsonAggStrictTableColumns::Id->select(),
                JsonbAggStrict::onManyLeftJoinedJsonObjects(
                    new JsonbBuildObject([
                        'rel_id' => new SqlExpression('rel.rel_id'),
                    ]),
                    new Column('rel_id', 'rel')
                )->toSQL(),
            )
            ->from(JsonAggStrictTable::name())
            ->leftJoin(
                JsonAggStrictTable::name(),
                '(VALUES (\'3bbeb63a-c925-4796-a68f-8b4ab451f128\'))',
                'AS rel (rel_id)',
                'rel.rel_id::UUID = json_agg_strict_expression.id'
            )
            ->groupBy(JsonAggStrictTableColumns::Id->use())
            ->orderBy(JsonAggStrictTableColumns::Id->use(), 'ASC');

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                [
                    '3bbeb63a-c925-4796-a68f-8b4ab451f128',
                    '[{"rel_id": "3bbeb63a-c925-4796-a68f-8b4ab451f128"}]',
                ],
                [
                    'd2ec80fc-07bc-4117-af07-750ea3c67092',
                    '[]',
                ],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_apply_json_strict_on_many_left_joined_json_objects_ordered_by(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb
            ->select(
                JsonAggStrictTableColumns::Id->select(),
                JsonbAggStrict::onManyLeftJoinedJsonObjects(
                    new JsonbBuildObject([
                        'rel_id' => new SqlExpression('rel.rel_id'),
                    ]),
                    new Column('rel_id', 'rel'),
                    new OrderBy(OrderBy::field(new SqlExpression('rel.rel_id'), OrderBy::DESC))
                )->toSQL(),
            )
            ->from(JsonAggStrictTable::name())
            ->leftJoin(
                JsonAggStrictTable::name(),
                '(VALUES (\'3bbeb63a-c925-4796-a68f-8b4ab451f128\'), (\'d2ec80fc-07bc-4117-af07-750ea3c67092\'))',
                'AS rel (rel_id)',
                'rel.rel_id::UUID IS NOT NULL'
            )
            ->groupBy(JsonAggStrictTableColumns::Id->use());

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                [
                    '3bbeb63a-c925-4796-a68f-8b4ab451f128',
                    '[{"rel_id": "d2ec80fc-07bc-4117-af07-750ea3c67092"}, {"rel_id": "3bbeb63a-c925-4796-a68f-8b4ab451f128"}]',
                ],
                [
                    'd2ec80fc-07bc-4117-af07-750ea3c67092',
                    '[{"rel_id": "d2ec80fc-07bc-4117-af07-750ea3c67092"}, {"rel_id": "3bbeb63a-c925-4796-a68f-8b4ab451f128"}]',
                ],
            ],
            $actualResults
        );
    }
}

class JsonAggStrictTable extends Table
{
    public static function name(): string
    {
        return 'json_agg_strict_expression';
    }

    public static function columns(): Columns
    {
        return Columns::for(JsonAggStrictTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name(), [
            new DoctrineColumn(JsonAggStrictTableColumns::Id->value, new GuidType()),
        ]);
    }
}

enum JsonAggStrictTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';

    public function linkedTableClass(): string
    {
        return JsonAggStrictTable::class;
    }
}
