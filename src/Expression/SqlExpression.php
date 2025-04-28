<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use function Psl\Str\replace;
use function Psl\Type\non_empty_string;

final readonly class SqlExpression implements Expression
{
    /**
     * @param non-empty-string $expression
     */
    public function __construct(private string $expression)
    {
    }

    public static function parameter(string $parameterName): self
    {
        return new self(non_empty_string()->assert($parameterName));
    }

    /**
     * @param non-empty-string      $reference
     * @param non-empty-string|null $alias
     */
    public static function tableReference(string $reference, ?string $alias = null): Expression
    {
        return null !== $alias ? new Alias(new self($reference), $alias) : new self($reference);
    }

    public static function null(): self
    {
        return new self('NULL');
    }

    public static function true(): self
    {
        return new self('TRUE');
    }

    public static function false(): self
    {
        return new self('FALSE');
    }

    public static function parenthesized(string $raw): self
    {
        return new self('('.$raw.')');
    }

    public static function int(int $value): self
    {
        return new self((string) $value);
    }

    /**
     * @param non-empty-string $expression
     */
    public static function escapePlaceholder(string $expression): self
    {
        return new self(non_empty_string()->assert(replace($expression, '?', '??')));
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return $this->expression;
    }
}
