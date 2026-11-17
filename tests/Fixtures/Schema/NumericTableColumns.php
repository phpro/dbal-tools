<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Schema;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;

enum NumericTableColumns: string implements TableColumnsInterface
{
    case SmallInt = 'small_int';
    case Integer = 'integer';
    case BigInt = 'big_int';

    use TableColumnsTrait;

    public function linkedTableClass(): string
    {
        return UsersTable::class;
    }
}
