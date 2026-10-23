<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use function Psl\Vec\map;

final readonly class In implements Expression
{
    public function __construct(
        private Expression $subject,
        private Expressions $in,
    ) {
    }

    /**
     * @template Tk
     * @template Tv
     *
     * @param iterable<Tk, Tv>          $iterable
     * @param (Closure(Tv): Expression) $function
     */
    public static function fromIterable(
        Expression $subject,
        iterable $iterable,
        \Closure $function,
    ): self {
        return new self(
            $subject,
            new Expressions(...map($iterable, $function)),
        );
    }

    /**
     * Binding the values as one JSON array keeps large lists under the bound parameter limit of PostgreSQL.
     * The elements are text, so pass an element type when the subject is not a text column.
     *
     * @param non-empty-string|null $elementType
     */
    public static function jsonbArrayElementsText(
        Expression $subject,
        Expression $jsonArray,
        ?string $elementType = null,
    ): self {
        $elements = new JsonbArrayElementsText($jsonArray);
        $selected = null !== $elementType ? new Cast($elementType, $elements) : $elements;

        return new self(
            $subject,
            new Expressions(new SqlExpression('SELECT '.$selected->toSQL())),
        );
    }

    public function toSQL(): string
    {
        return sprintf(
            '%s IN (%s)',
            $this->subject->toSQL(),
            $this->in->join(', ')->toSQL(),
        );
    }
}
