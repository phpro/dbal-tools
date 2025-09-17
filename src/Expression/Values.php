<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Values implements Expression
{
    private Expressions $rows;

    public function __construct(
        Expression $row,
        Expression ...$rows,
    ) {
        $this->rows = new Expressions($row, ...$rows);
    }

    public static function row(Expression $field1, Expression ...$fields): SqlExpression
    {
        return SqlExpression::parenthesized(
            new Expressions($field1, ...$fields)->join(', ')
        );
    }

    public static function parenthesized(Expression $row, Expression ...$rows): SqlExpression
    {
        return SqlExpression::parenthesized(new self($row, ...$rows));
    }

    public function toSQL(): string
    {
        return 'VALUES '.$this->rows->join(', ')->toSQL();
    }
}
