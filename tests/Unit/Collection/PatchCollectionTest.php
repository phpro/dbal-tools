<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Collection;

use Phpro\DbalTools\Collection\PatchCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Ref;

final class PatchCollectionTest extends TestCase
{
    #[Test]
    public function it_knows_what_to_patch(): void
    {
        $ref = new Ref([
            'create' => [],
            'update' => [],
            'delete' => [],
        ]);

        $newList = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
        ];

        $oldList = [
            ['id' => 1, 'name' => 'AUpdated'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 4, 'name' => 'D'],
        ];

        $patcher = new PatchCollection();
        $patcher->patch(
            $oldList,
            $newList,
            static fn (array $item): int => $item['id'],
            static fn (array $item) => $ref->value['create'][] = $item,
            static fn (array $item) => $ref->value['update'][] = $item,
            static fn (array $item) => $ref->value['delete'][] = $item,
        );

        self::assertEquals([
            ['id' => 4, 'name' => 'D'],
        ], $ref->value['create']);

        self::assertEquals([
            ['id' => 1, 'name' => 'AUpdated'],
            ['id' => 2, 'name' => 'B'],
        ], $ref->value['update']);

        self::assertEquals([
            ['id' => 3, 'name' => 'C'],
        ], $ref->value['delete']);
    }
}
