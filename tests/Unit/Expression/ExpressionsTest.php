<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Expression;

use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Expression\Expressions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExpressionsTest extends TestCase
{
    #[Test]
    public function it_throws_on_empty_expressions(): void
    {
        $this->expectExceptionMessage('At least one expression is required.');
        new Expressions();
    }

    #[Test]
    public function it_contains_multiple_expressions(): void
    {
        $expressions = new Expressions(
            $expression1 = $this->createExpression(),
            $expression2 = $this->createExpression(),
        );

        self::assertCount(2, $expressions);
        self::assertSame([$expression1, $expression2], iterator_to_array($expressions));
    }

    #[Test]
    public function it_can_traverse_expressions(): void
    {
        $expressions = new Expressions(
            $this->createExpression('hello'),
            $this->createExpression('world'),
        );
        $actual = $expressions->traverse(static fn (Expression $expression): string => $expression->toSQL());

        self::assertSame(['hello', 'world'], $actual);
    }

    #[Test]
    public function it_can_grab_expression_values(): void
    {
        $expressions = new Expressions(
            $this->createExpression('hello'),
            $this->createExpression('world'),
        );
        $actual = $expressions->values();

        self::assertSame(['hello', 'world'], $actual);
    }

    #[Test]
    public function it_can_build_from_nullables(): void
    {
        $expressions = Expressions::fromNullable(
            $expression1 = $this->createExpression(),
            $expression2 = null,
        );

        self::assertCount(1, $expressions);
        self::assertSame([$expression1], iterator_to_array($expressions));
    }

    #[Test]
    public function it_throws_on_empty_nullable_expressions(): void
    {
        $this->expectExceptionMessage('At least one expression is required.');
        Expressions::fromNullable(null);
    }

    #[Test]
    public function it_can_join_into_a_single_expression(): void
    {
        $expressions = new Expressions(
            $this->createExpression('hello'),
            $this->createExpression('world'),
        );

        $actual = $expressions->join(' ');
        self::assertSame('hello world', $actual->toSQL());
    }

    private function createExpression(string $name = 'hello'): Expression
    {
        return new class($name) implements Expression {
            public function __construct(private string $name)
            {

            }

            public function toSQL(): string
            {
                return $this->name;
            }
        };
    }
}
