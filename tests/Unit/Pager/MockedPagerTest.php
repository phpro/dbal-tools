<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Pager;

use Phpro\DbalTools\Pager\MockedPager;
use Phpro\DbalTools\Pager\Pagination;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function Psl\Vec\reproduce;

final class MockedPagerTest extends TestCase
{
    #[Test]
    public function it_can_mock_pager(): void
    {
        $pager = self::createPager(new Pagination(page: 2, limit: 3));

        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(10, $pager->totalResults());
        self::assertSame(4, $pager->totalPages());

        self::assertSame([4, 5, 6], [...$pager]);
        self::assertSame([5, 6, 7], $pager->traverse(static fn (int $item) => $item + 1));
    }

    #[Test]
    public function it_has_defaults_on_empty_resultsets(): void
    {
        $pager = new MockedPager(new Pagination(page: 2, limit: 3), []);

        self::assertSame(0, $pager->totalResults());
        self::assertSame(1, $pager->totalPages());
    }

    public static function createPager(Pagination $pagination): MockedPager
    {
        return new MockedPager(
            $pagination,
            reproduce(10, static fn (int $i) => $i),
        );
    }
}
