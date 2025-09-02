<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Schema;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;

enum PostsTableColumns: string implements TableColumnsInterface
{
    case Id = 'post_id';
    case UserId = 'user_id';
    case Post = 'post';

    use TableColumnsTrait;

    public function linkedTableClass(): string
    {
        return PostsTable::class;
    }
}
