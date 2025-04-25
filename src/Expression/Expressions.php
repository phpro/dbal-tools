<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use function Psl\invariant;
use function Psl\Vec\filter_nulls;
use function Psl\Vec\map;

/**
 * @template-implements \IteratorAggregate<Expression>
 */
final readonly class Expressions implements \IteratorAggregate, \Countable
{
    /**
     * @var non-empty-list<Expression>
     */
    private array $expressions;

    /**
     * @param Expression ...$expressions
     *
     * @no-named-arguments
     */
    public function __construct(Expression ...$expressions)
    {
        invariant(count($expressions) > 0, 'At least one expression is required.');
        $this->expressions = $expressions;
    }

    /**
     * @param Expression $expressions
     *
     * @no-named-arguments
     */
    public static function fromNullable(?Expression ...$expression): self
    {
        return new self(...filter_nulls($expression));
    }

    public function getIterator(): \Traversable
    {
        yield from $this->expressions;
    }

    public function count(): int
    {
        return count($this->expressions);
    }

    /**
     * @template M
     *
     * @param \Closure(Expression): M $mapper
     *
     * @return non-empty-list<M>
     */
    public function traverse(\Closure $mapper): array
    {
        return map($this->expressions, $mapper);
    }

    public function join(string $separator): Expression
    {
        /** @var non-empty-string $rawExpression */
        $rawExpression = \Psl\Str\join(
            map($this->expressions, static fn (Expression $expression): string => $expression->toSQL()),
            $separator,
        );

        return new SqlExpression($rawExpression);
    }
}
