<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\ConcatWs;
use Phpro\DbalTools\Expression\From;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConcatWsTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_concat_ws(): void
    {
        $concatWs = new ConcatWs(
            new LiteralString(' '),
            new LiteralString('b'),
            new LiteralString('a'),
            new LiteralString('c')
        );

        $sql = 'SELECT '.$concatWs->toSQL();
        $result = self::connection()->executeQuery($sql);

        self::assertSame('b a c', $result->fetchOne());
    }

    #[Test]
    public function it_can_concat_ws_order_by(): void
    {
        $from = new From(
            SqlExpression::tableReference('(VALUES (\'b\', \'b\'), (\'a\', \'a2\'), (\'a\', \'a1\'))', 'foo (a, b)'),
        );
        $orderBy = new ConcatWs(
            new LiteralString(' '),
            new Column('b', 'foo'),
            new Column('a', 'foo')
        );

        $sql = <<<EOSQL
            SELECT foo.a, foo.b
            {$from->toSQL()}
            ORDER BY {$orderBy->toSQL()}
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [
                ['a' => 'a', 'b' => 'a1'],
                ['a' => 'a', 'b' => 'a2'],
                ['a' => 'b', 'b' => 'b'],
            ],
            $result->fetchAllAssociative(),
        );
    }
}
