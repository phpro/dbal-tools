<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\JsonbObjectAgg;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Json\decode;

final class JsonbObjectAggTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_renders_sql(): void
    {
        self::assertSame(
            'jsonb_object_agg(locale, row_data)',
            new JsonbObjectAgg(new SqlExpression('locale'), new SqlExpression('row_data'))->toSQL()
        );
    }

    #[Test]
    public function it_renders_sql_ordered_by(): void
    {
        $key = new SqlExpression('locale');

        self::assertSame(
            'jsonb_object_agg(locale, row_data ORDER BY locale)',
            new JsonbObjectAgg($key, new SqlExpression('row_data'), new OrderBy($key))->toSQL()
        );
    }

    #[Test]
    public function it_can_aggregate_keyed_objects(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            new JsonbObjectAgg(new SqlExpression('t.locale'), new SqlExpression('t.label'))->toSQL()
        )->from("(VALUES ('nl', 'Hallo'), ('fr', 'Bonjour'))", 'AS t (locale, label)');

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(['fr' => 'Bonjour', 'nl' => 'Hallo'], decode($actualResults[0]));
    }

    #[Test]
    public function it_keeps_the_last_value_for_a_duplicate_key_in_order(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            new JsonbObjectAgg(
                new SqlExpression('t.locale'),
                new SqlExpression('t.label'),
                new OrderBy(OrderBy::field(new SqlExpression('t.position'), OrderBy::DESC))
            )->toSQL()
        )->from("(VALUES ('nl', 'Eerste', 1), ('nl', 'Tweede', 2))", 'AS t (locale, label, position)');

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(['nl' => 'Eerste'], decode($actualResults[0]));
    }
}
