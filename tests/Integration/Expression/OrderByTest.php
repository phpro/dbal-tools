<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\From;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OrderByTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {
    }

    #[Test]
    public function it_can_order_by(): void
    {
        $from = new From(
            SqlExpression::tableReference('(VALUES (\'b\', \'b\'), (\'a\', \'a2\'), (\'a\', \'a1\'))', 'foo (a, b)'),
        );
        $orderBy = new OrderBy(
            OrderBy::field(new Column('a', 'foo')),
            OrderBy::field(new Column('a', 'foo'), OrderBy::DESC),
        );

        $sql = <<<EOSQL
            SELECT foo.a, foo.b
            {$from->toSQL()}
            {$orderBy->toSQL()}
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [
                ['a' => 'a', 'b' => 'a2'],
                ['a' => 'a', 'b' => 'a1'],
                ['a' => 'b', 'b' => 'b'],
            ],
            $result->fetchAllAssociative(),
        );
    }
}
