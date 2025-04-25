<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class LiteralStringTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_use_literal_strings(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new LiteralString('a'))->toSQL(),
            (new LiteralString('a\'b'))->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], 'a');
        self::assertSame($actualResults[1], 'a\'b');
    }
}
