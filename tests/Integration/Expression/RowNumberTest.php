<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Alias;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\RowNumber;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class RowNumberTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {
    }

    #[Test]
    public function it_can_get_row_number_without_window_function(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            'foo.id',
            new Alias(new RowNumber(), 'row')->toSQL(),
        )->from(
            '(VALUES (1), (2), (3))',
            'foo (id)'
        );

        $actualResults = $qb->fetchAllAssociative();

        self::assertSame(
            [
                ['id' => 1, 'row' => 1],
                ['id' => 2, 'row' => 2],
                ['id' => 3, 'row' => 3],
            ],
            $actualResults,
        );
    }

    #[Test]
    public function it_can_get_row_number_with_window_function(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            'foo.id',
            new Alias(RowNumber::over(
                new OrderBy(OrderBy::field(new Column('id', 'foo'), OrderBy::DESC))
            ), 'row')->toSQL(),
        )->from(
            '(VALUES (1), (2), (3))',
            'foo (id)'
        )->orderBy('id', 'ASC');

        $actualResults = $qb->fetchAllAssociative();

        self::assertSame(
            [
                ['id' => 1, 'row' => 3],
                ['id' => 2, 'row' => 2],
                ['id' => 3, 'row' => 1],
            ],
            $actualResults,
        );
    }
}
