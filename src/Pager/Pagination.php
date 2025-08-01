<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pager;

final readonly class Pagination
{
    /**
     * @param int<1,max> $page  - Page number, starting from 1
     * @param int<1,max> $limit - Number of items per page
     */
    public function __construct(
        public int $page,
        public int $limit,
    ) {
    }
}
