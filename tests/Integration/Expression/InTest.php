<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Expressions;
use Phpro\DbalTools\Expression\In;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class InTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_evaluate_sum(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('countable')
            ->from(
                '(VALUES (1), (2), (3))',
                'foo (countable)'
            )->where(
                (new In(new SqlExpression('countable'), new Expressions(
                    new SqlExpression('1'),
                    new SqlExpression('3'),
                )))->toSQL()
            );

        $actualResults = $qb->fetchAllAssociative();
        self::assertCount(2, $actualResults);
        self::assertSame(1, $actualResults[0]['countable']);
        self::assertSame(3, $actualResults[1]['countable']);
    }
}
