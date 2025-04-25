<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Cases implements Expression
{
    /**
     * @param Expressions     $conditions - use self::when() for building up the set of expression conditions
     * @param Expression|null $else
     */
    public function __construct(
        private Expressions $conditions,
        private ?Expression $else = null,
    ) {
    }

    public static function when(Expression $condition, Expression $then): Expression
    {
        return new SqlExpression(
            'WHEN '.$condition->toSQL().' THEN '.$then->toSQL()
        );
    }

    public function toSQL(): string
    {
        $sql = 'CASE ';
        $sql .= $this->conditions->join(' ')->toSQL();

        if (null !== $this->else) {
            $sql .= ' ELSE '.$this->else->toSQL();
        }

        return $sql.' END';
    }
}
