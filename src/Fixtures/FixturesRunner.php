<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Fixtures;

use function Psl\Type\instance_of;
use function Psl\Type\vec;

final readonly class FixturesRunner
{
    /**
     * @var list<Fixture<object>>
     */
    public array $fixtures;

    /**
     * @param iterable<Fixture<object>> $fixtures
     */
    public function __construct(iterable $fixtures)
    {
        $this->fixtures = vec(instance_of(Fixture::class))->coerce($fixtures);
    }

    /**
     * @return \Generator<string, object>
     */
    public function execute(?string $type = null): \Generator
    {
        foreach ($this->fixtures as $fixture) {
            if (null !== $type && $fixture->type() !== $type) {
                continue;
            }

            yield from $fixture->execute();
        }
    }
}
