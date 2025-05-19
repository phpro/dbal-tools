<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Schema;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;

enum NonExistingTableColumns: string implements TableColumnsInterface
{
    case NonExisting = 'non_existing';

    use TableColumnsTrait;

    public function linkedTableClass(): string
    {
        return UsersTable::class;
    }
}
