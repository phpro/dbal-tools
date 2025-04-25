<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Cases;
use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\Expressions;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class CasesTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_perform_when_cases(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            $cases = (new Cases(
                new Expressions(
                    Cases::when(
                        Comparison::equal(new Column('character', 'foo'), new LiteralString('a')),
                        new LiteralString('Adrian')
                    ),
                    Cases::when(
                        Comparison::equal(new Column('character', 'foo'), new LiteralString('b')),
                        new LiteralString('Billy'),
                    )
                )
            ))->toSQL()
        )->from(
            '(VALUES (\'a\'), (\'b\'), (\'c\'))',
            'foo (character)'
        );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['Adrian'],
                ['Billy'],
                [null],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_perform_when_cases_with_then(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Cases(
                new Expressions(
                    Cases::when(
                        Comparison::equal(new Column('character', 'foo'), new LiteralString('a')),
                        new LiteralString('Adrian')
                    ),
                    Cases::when(
                        Comparison::equal(new Column('character', 'foo'), new LiteralString('b')),
                        new LiteralString('Billy'),
                    )
                ),
                new LiteralString('Charlie')
            ))->toSQL()
        )->from(
            '(VALUES (\'a\'), (\'b\'), (\'c\'))',
            'foo (character)'
        );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['Adrian'],
                ['Billy'],
                ['Charlie'],
            ],
            $actualResults
        );
    }
}
