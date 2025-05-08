<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class Interval implements Expression
{
    private function __construct(
        private Expression $interval,
    ) {
    }

    public static function literal(string $interval): self
    {
        return new self(new LiteralString($interval));
    }

    public static function days(int $days): self
    {
        return self::literal($days.' days');
    }

    public function toSQL(): string
    {
        return 'INTERVAL '.$this->interval->toSQL();
    }
}
