<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Type;

final readonly class Uuid
{
    public function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
