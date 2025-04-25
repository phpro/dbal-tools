<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Fixtures;

/**
 * @template T of object
 */
interface Fixture
{
    /**
     * @return class-string<T>
     */
    public function type(): string;

    /**
     * @return list<string>
     */
    public function tables(): array;

    /**
     * @return \Generator<string, T> "uuid" => model
     */
    public function execute(): \Generator;

    /**
     * @param T $x
     */
    public function exists(object $x): bool;
}
