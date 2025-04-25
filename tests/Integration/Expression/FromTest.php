<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\From;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class FromTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {
    }

    #[Test]
    public function it_can_select_from(): void
    {
        $from = new From(
            SqlExpression::tableReference('(VALUES (\'a\'), (\'b\'), (\'c\'))', 'foo (character)'),
            SqlExpression::tableReference('(VALUES (\'a\'), (\'e\'), (\'f\'))', 'bar (character)'),
        );

        $sql = <<<EOSQL
            SELECT foo.character
            {$from->toSQL()}
            WHERE foo.character = bar.character
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [['character' => 'a']],
            $result->fetchAllAssociative(),
        );
    }
}
