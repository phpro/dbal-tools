<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Schema;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;

enum UsersTableColumns: string implements TableColumnsInterface
{
    case Id = 'user_id';
    case Username = 'username';
    case FirstName = 'first_name';
    case LastName = 'last_name';

    use TableColumnsTrait;

    public function linkedTableClass(): string
    {
        return UsersTable::class;
    }
}
