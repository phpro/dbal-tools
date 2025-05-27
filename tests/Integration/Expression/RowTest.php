<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\Row;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class RowTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_build_row(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            new Row(
                new LiteralString('hello'),
                new LiteralString('world')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], '(hello,world)');
    }
}
