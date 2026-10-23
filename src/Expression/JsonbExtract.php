<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

final readonly class JsonbExtract implements Expression
{
    /**
     * @param non-empty-string $operator
     */
    private function __construct(
        private Expression $json,
        private string $operator,
        private string $key,
    ) {
    }

    public static function field(Expression $json, string $key): self
    {
        return new self($json, '->', $key);
    }

    public static function text(Expression $json, string $key): self
    {
        return new self($json, '->>', $key);
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            '%s %s %s',
            $this->json->toSQL(),
            $this->operator,
            new LiteralString($this->key)->toSQL()
        );
    }
}
