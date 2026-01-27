<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Fixtures;

use Phpro\DbalTools\Fixtures\Fixture;
use Phpro\DbalTools\Fixtures\FixturesRunner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FixturesRunnerTest extends TestCase
{
    #[Test]
    public function it_can_run_all_fixtures(): void
    {
        $fixture1 = $this->createStub(Fixture::class);
        $fixture1->method('type')->willReturn('type1');
        $fixture1->method('execute')->willReturnCallback(function () {
            yield '1' => (object) ['type1'];
            yield '2' => (object) ['type1'];
        });
        $fixture2 = $this->createStub(Fixture::class);
        $fixture2->method('type')->willReturn('type2');
        $fixture2->method('execute')->willReturnCallback(function () {
            yield '3' => (object) ['type2'];
            yield '4' => (object) ['type2'];
        });
        $runner = $this->createRunner($fixture1, $fixture2);

        $actual = $runner->execute();

        self::assertEquals([
            '1' => (object) ['type1'],
            '2' => (object) ['type1'],
            '3' => (object) ['type2'],
            '4' => (object) ['type2'],
        ], iterator_to_array($actual));
    }

    #[Test]
    public function it_can_run_fixtures_of_type(): void
    {
        $fixture1 = $this->createStub(Fixture::class);
        $fixture1->method('type')->willReturn('type1');
        $fixture1->method('execute')->willReturnCallback(function () {
            yield '1' => (object) ['type1'];
            yield '2' => (object) ['type1'];
        });
        $fixture2 = $this->createStub(Fixture::class);
        $fixture2->method('type')->willReturn('type2');
        $fixture2->method('execute')->willReturnCallback(function () {
            yield '3' => (object) ['type2'];
            yield '4' => (object) ['type2'];
        });
        $runner = $this->createRunner($fixture1, $fixture2);

        $actual = $runner->execute('type2');

        self::assertEquals([
            '3' => (object) ['type2'],
            '4' => (object) ['type2'],
        ], iterator_to_array($actual));
    }

    private function createRunner(Fixture ...$fixtures): FixturesRunner
    {
        return new FixturesRunner($fixtures);
    }
}
