<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\From;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Expression\Values;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ValuesTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_build_values(): void
    {
        $from = new From(
            SqlExpression::tableReference(
                Values::parenthesized(
                    Values::row(new LiteralString('a1'), new LiteralString('b1')),
                    Values::row(new LiteralString('a2'), new LiteralString('b2')),
                    Values::row(new LiteralString('a3'), new LiteralString('b3')),
                ),
                'foo (a, b)'
            )
        );

        $sql = <<<EOSQL
            SELECT foo.a, foo.b
            {$from->toSQL()}
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [
                ['a' => 'a1', 'b' => 'b1'],
                ['a' => 'a2', 'b' => 'b2'],
                ['a' => 'a3', 'b' => 'b3'],
            ],
            $result->fetchAllAssociative(),
        );
    }
}
