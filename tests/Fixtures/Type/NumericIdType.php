<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Type;

use Doctrine\DBAL\Types\IntegerType;

final class NumericIdType extends IntegerType
{
    public const string NAME = 'numeric_id';
}
