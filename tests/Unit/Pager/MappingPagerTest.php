<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Pager;

use Phpro\DbalTools\Pager\MappingPager;
use Phpro\DbalTools\Pager\Pagination;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MappingPagerTest extends TestCase
{
    #[Test]
    public function it_can_map_pager_results(): void
    {
        $increment = static fn (int $item) => $item + 1;
        $pager = self::createPager(new Pagination(page: 2, limit: 3), $increment);
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(10, $pager->totalResults());
        self::assertSame(4, $pager->totalPages());
        self::assertSame([5, 6, 7], [...$pager]);
        self::assertSame([6, 7, 8], $pager->traverse($increment));
    }

    private static function createPager(Pagination $pagination, \Closure $mapper): MappingPager
    {
        return new MappingPager(
            MockedPagerTest::createPager($pagination),
            $mapper,
        );
    }
}
