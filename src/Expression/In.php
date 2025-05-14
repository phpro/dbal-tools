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

    public function toSQL(): string
    {
        return sprintf(
            '%s IN (%s)',
            $this->subject->toSQL(),
            $this->in->join(', ')->toSQL(),
        );
    }
}
