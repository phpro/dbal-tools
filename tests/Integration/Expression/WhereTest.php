<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\From;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Expression\Where;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class WhereTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {
    }

    #[Test]
    public function it_can_apply_where(): void
    {
        $from = new From(
            SqlExpression::tableReference('(VALUES (\'a\'), (\'b\'), (\'c\'))', 'foo (character)'),
            SqlExpression::tableReference('(VALUES (\'a\'), (\'e\'), (\'f\'))', 'bar (character)'),
        );
        $where = new Where(
            Comparison::equal(new Column('character', 'foo'), new Column('character', 'bar')),
        );

        $sql = <<<EOSQL
            SELECT foo.character
            {$from->toSQL()}
            {$where->toSQL()}
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [['character' => 'a']],
            $result->fetchAllAssociative(),
        );
    }
}
