<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\ILike;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ILikeTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_perform_case_insensitive_like_checks(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new ILike(
                new LiteralString('helLO'),
                new LiteralString('HeLlo')
            ))->toSQL(),
            (new ILike(
                new LiteralString('helLO'),
                new LiteralString('HeL%')
            ))->toSQL(),
            (new ILike(
                new LiteralString('hello'),
                new LiteralString('world')
            ))->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], true);
        self::assertSame($actualResults[1], true);
        self::assertSame($actualResults[2], false);
    }
}
