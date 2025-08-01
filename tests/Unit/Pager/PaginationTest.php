<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Pager;

use Phpro\DbalTools\Pager\Pagination;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    #[Test]
    public function it_knows_about_pagination(): void
    {
        $pagination = new Pagination(
            page: 1,
            limit: 15,
        );

        self::assertSame(1, $pagination->page);
        self::assertSame(15, $pagination->limit);
    }
}
