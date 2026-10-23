<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Cast;
use Phpro\DbalTools\Expression\Expressions;
use Phpro\DbalTools\Expression\In;
use Phpro\DbalTools\Expression\JsonbExtract;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Json\encode;

final class InTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_evaluate_sum(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('countable')
            ->from(
                '(VALUES (1), (2), (3))',
                'foo (countable)'
            )->where(
                (new In(new SqlExpression('countable'), new Expressions(
                    new SqlExpression('1'),
                    new SqlExpression('3'),
                )))->toSQL()
            );

        $actualResults = $qb->fetchAllAssociative();
        self::assertCount(2, $actualResults);
        self::assertSame(1, $actualResults[0]['countable']);
        self::assertSame(3, $actualResults[1]['countable']);
    }

    #[Test]
    public function it_can_evaluate_from_iterable(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('countable')
            ->from(
                '(VALUES (1), (2), (3))',
                'foo (countable)'
            )->where(
                In::fromIterable(new SqlExpression('countable'), ['1', '3'], static fn (string $value) => new SqlExpression($value))->toSQL()
            );

        $actualResults = $qb->fetchAllAssociative();
        self::assertCount(2, $actualResults);
        self::assertSame(1, $actualResults[0]['countable']);
        self::assertSame(3, $actualResults[1]['countable']);
    }

    #[Test]
    public function it_renders_jsonb_array_elements_text_subselect(): void
    {
        self::assertSame(
            "col IN (SELECT jsonb_array_elements_text(:param::JSONB -> 'key'))",
            In::jsonbArrayElementsText(
                new SqlExpression('col'),
                JsonbExtract::field(Cast::jsonb(SqlExpression::parameter(':param')), 'key'),
            )->toSQL()
        );
    }

    #[Test]
    public function it_renders_jsonb_array_elements_text_subselect_with_element_type(): void
    {
        self::assertSame(
            'col IN (SELECT jsonb_array_elements_text(:param::JSONB)::INT)',
            In::jsonbArrayElementsText(
                new SqlExpression('col'),
                Cast::jsonb(SqlExpression::parameter(':param')),
                'INT',
            )->toSQL()
        );
    }

    #[Test]
    public function it_can_evaluate_jsonb_array_elements_text(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('name')
            ->from(
                "(VALUES ('jos'), ('it''s'), ('mieke'))",
                'foo (name)'
            )->where(
                In::jsonbArrayElementsText(
                    new SqlExpression('name'),
                    JsonbExtract::field(Cast::jsonb(SqlExpression::parameter(':param')), 'names'),
                )->toSQL()
            )
            ->setParameter('param', encode(['names' => ['jos', "it's"]]));

        self::assertSame(['jos', "it's"], $qb->fetchFirstColumn());
    }

    #[Test]
    public function it_can_evaluate_jsonb_array_elements_text_with_element_type(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('countable')
            ->from(
                '(VALUES (1), (2), (3))',
                'foo (countable)'
            )->where(
                In::jsonbArrayElementsText(
                    new SqlExpression('countable'),
                    Cast::jsonb(SqlExpression::parameter(':param')),
                    'INT',
                )->toSQL()
            )
            ->setParameter('param', encode([1, 3]));

        self::assertSame([1, 3], $qb->fetchFirstColumn());
    }
}
