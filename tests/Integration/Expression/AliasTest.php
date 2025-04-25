<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Alias;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class AliasTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_alias(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Alias(
                new SqlExpression('1'),
                'alias'
            ))->toSQL()
        );

        $actualResults = $qb->fetchAssociative();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults['alias'], 1);
    }
}
