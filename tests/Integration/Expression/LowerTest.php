<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\Lower;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class LowerTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    #[DataProvider('lowerDataProvider')]
    public function it_can_evaluate_lower($expression, $expectedResult): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            new Lower($expression)->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function lowerDataProvider(): \Generator
    {
        yield 'uppercase' => [new LiteralString('HELLO'), 'hello'];
        yield 'mixed_case' => [new LiteralString('HeLLo WoRLD'), 'hello world'];
        yield 'already_lowercase' => [new LiteralString('hello'), 'hello'];
        yield 'null_value' => [SqlExpression::null(), null];
    }
}
