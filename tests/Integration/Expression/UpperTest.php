<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Expression\Upper;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class UpperTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    #[DataProvider('upperDataProvider')]
    public function it_can_evaluate_upper($expression, $expectedResult): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Upper($expression))->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function upperDataProvider(): \Generator
    {
        yield 'lowercase' => [new LiteralString('hello'), 'HELLO'];
        yield 'mixed_case' => [new LiteralString('HeLLo WoRLD'), 'HELLO WORLD'];
        yield 'already_uppercase' => [new LiteralString('HELLO'), 'HELLO'];
        yield 'null_value' => [SqlExpression::null(), null];
    }
}
