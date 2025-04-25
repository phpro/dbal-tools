<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Pagerfanta\Adapter;

use Pagerfanta\Adapter\ArrayAdapter;
use Phpro\DbalTools\Pagerfanta\Adapter\MappingAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MappingAdapterTest extends TestCase
{
    #[Test]
    public function it_wraps_a_pager_adapter_with_a_callback(): void
    {
        $wrappedAdapter = new ArrayAdapter([
            ['name' => 'test'],
            ['name' => 'test2'],
            ['name' => 'test3'],
        ]);
        $mapper = fn (array $result): string => $result['name'];

        $adapter = new MappingAdapter($wrappedAdapter, $mapper);

        $slice = iterator_to_array($adapter->getSlice(1, 1));

        self::assertSame(['test2'], $slice);
        self::assertSame(3, $adapter->getNbResults());
    }
}
