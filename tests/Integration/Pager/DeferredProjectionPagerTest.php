<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Pager;

use Doctrine\DBAL\Query\QueryBuilder;
use Phpro\DbalTools\Expression\Alias;
use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\Composite;
use Phpro\DbalTools\Expression\Count;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Expression\Factory\NamedParameter;
use Phpro\DbalTools\Expression\ILike;
use Phpro\DbalTools\Expression\JsonbAggStrict;
use Phpro\DbalTools\Expression\JsonbBuildObject;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Pager\DeferredProjectionPager;
use Phpro\DbalTools\Pager\Pagination;
use Phpro\DbalTools\Query\CompositeQuery;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use Phpro\DbalTools\Test\Manager\TransactionManager;
use PhproTest\DbalTools\Fixtures\Schema\PostsTable;
use PhproTest\DbalTools\Fixtures\Schema\PostsTableColumns;
use PhproTest\DbalTools\Fixtures\Schema\UsersTable;
use PhproTest\DbalTools\Fixtures\Schema\UsersTableColumns;
use PhproTest\DbalTools\Fixtures\Type\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use function Psl\Json\decode;
use function Psl\Vec\map;
use function Psl\Vec\values;

/**
 * The pager over a real schema, since its whole subject is one statement PostgreSQL actually plans: the
 * fold, the join it derives from the key, the total the window computes over the whole matching set, and
 * the order it applies at both levels.
 *
 * The world is 6 users of which user1 up to user5 own 1 up to 5 posts and user6 owns none, so a page
 * narrower than the answer can be asserted alongside a total that disagrees both with the page and with
 * the 15 rows a naive join would count.
 */
final class DeferredProjectionPagerTest extends DbalReaderTestCase
{
    private const int USERS = 6;

    protected static function schemaTables(): array
    {
        return [
            UsersTable::class,
            PostsTable::class,
        ];
    }

    protected function createFixtures(): void
    {
        for ($i = 1; $i <= self::USERS; ++$i) {
            self::connection()->insert(UsersTable::name(), $this->fixtures['user'.$i] = [
                UsersTableColumns::Id->value => $userId = Uuid::generate()->value,
                UsersTableColumns::Username->value => 'user'.$i,
                UsersTableColumns::FirstName->value => 0 === $i % 2 ? 'even' : 'odd',
                UsersTableColumns::LastName->value => 'er'.$i,
            ]);

            // user6 owns no posts at all, which is the row whose aggregate comes back as NULL.
            for ($j = 1; $j < $i && $i < self::USERS; ++$j) {
                self::connection()->insert(PostsTable::name(), [
                    PostsTableColumns::Id->value => Uuid::generate()->value,
                    PostsTableColumns::UserId->value => $userId,
                    PostsTableColumns::Post->value => 'post '.$j.' of user '.$i,
                ]);
            }
        }
    }

    /**
     * The total counts every matching key while the page holds only its own, which is the whole premise:
     * the count window sits inside the narrow query, where `LIMIT` cannot reach it.
     */
    #[Test]
    #[TestWith([1, ['user1', 'user2']])]
    #[TestWith([2, ['user3', 'user4']])]
    #[TestWith([3, ['user5', 'user6']])]
    public function it_pages_the_answer_while_counting_all_of_it(int $page, array $expected): void
    {
        $pager = self::pager(new Pagination($page, 2));

        self::assertSame($expected, self::usernames($pager));
        self::assertSame(6, $pager->totalResults());
        self::assertSame(3, $pager->totalPages());
        self::assertSame($page, $pager->pagination()->page);
        self::assertSame(2, $pager->pagination()->limit);
    }

    /**
     * The total is the number of keys in the narrow set, not the number of rows the projection join
     * produces. That is the difference from counting over the fat query, where the one-to-many join
     * inflates every total.
     */
    #[Test]
    public function it_counts_keys_rather_than_joined_rows(): void
    {
        $joinedRows = (int) self::connection()->fetchOne(
            self::connection()->createQueryBuilder()
                ->select(UsersTableColumns::Id->apply(static fn ($column) => new Count($column)))
                ->from(UsersTable::name())
                ->innerJoin(...UsersTable::joinOntoPosts())
                ->getSQL(),
        );

        self::assertSame(10, $joinedRows);
        self::assertSame(6, self::pager(new Pagination(1, 2))->totalResults());
    }

    /**
     * The projection the pager exists for: a one-to-many aggregate of joined json objects, evaluated for
     * the two rows of the page rather than for all six.
     */
    #[Test]
    public function it_projects_every_row_of_the_page_with_its_aggregated_objects(): void
    {
        $rows = values(self::pager(new Pagination(2, 2)));

        self::assertSame(['user3', 'user4'], self::usernames(self::pager(new Pagination(2, 2))));
        self::assertSame(['post 1 of user 3', 'post 2 of user 3'], self::posts($rows[0]));
        self::assertSame(
            ['post 1 of user 4', 'post 2 of user 4', 'post 3 of user 4'],
            self::posts($rows[1]),
        );
    }

    /**
     * A key without related rows is still a page row: the left join keeps it and the strict aggregate
     * drops the NULL, so its projection is an empty array rather than a missing row.
     */
    #[Test]
    public function it_keeps_a_row_whose_aggregate_is_empty(): void
    {
        $rows = values(self::pager(new Pagination(3, 2)));

        self::assertSame('user6', $rows[1][UsersTableColumns::Username->value]);
        self::assertSame([], self::posts($rows[1]));
    }

    /**
     * A CTE the caller registered survives the fold: `moveMainQueryToSubQuery()` merges the narrow query
     * into the `WITH` list it already holds rather than replacing it.
     */
    #[Test]
    public function a_caller_registered_cte_survives_the_fold(): void
    {
        $composite = self::matchingKeys();
        $composite->addSubQuery(
            'prolific',
            self::connection()->createQueryBuilder()
                ->select(PostsTableColumns::UserId->select())
                ->from(PostsTable::name())
                ->groupBy(PostsTableColumns::UserId->use())
                ->having(PostsTableColumns::Id->apply(static fn ($column) => new Count($column)).' >= 3'),
        );
        $composite->mainQuery()->innerJoin(...$composite->joinOntoCte(
            'prolific',
            UsersTable::name(),
            UsersTableColumns::Id->column(),
            PostsTableColumns::UserId->column(),
        ));

        $pager = self::createPager($composite, new Pagination(1, 10));

        self::assertSame(['user4', 'user5'], self::usernames($pager));
        self::assertSame(2, $pager->totalResults());
        self::assertSame(1, $pager->totalPages());
    }

    /**
     * A parameter bound on the narrow main query survives the fold too, which reads like a violation of
     * "bind on the main query": the very same builder moves into the `WITH` list, and
     * `CompositeQuery::execute()` merges every registered builder's parameters before the main query's.
     */
    #[Test]
    public function a_parameter_bound_on_the_narrow_query_survives_the_fold(): void
    {
        $composite = self::matchingKeys();
        $group = NamedParameter::createForTableColumn(
            $composite->mainQuery(),
            UsersTableColumns::FirstName,
            'even',
            ':group',
        );
        $composite->mainQuery()->where(
            Comparison::equal(UsersTableColumns::FirstName->column(), $group)->toSQL(),
        );

        $pager = self::createPager($composite, new Pagination(1, 10));

        self::assertSame(['user2', 'user4', 'user6'], self::usernames($pager));
        self::assertSame(3, $pager->totalResults());
    }

    /**
     * A filter on related rows belongs in the matching keys, as `EXISTS` so each key stays unique, and the
     * inner join onto the page is what carries it into the projection: the projection never repeats the
     * filter, so it still aggregates every post of a matching user rather than only the matching one.
     */
    #[Test]
    public function a_related_row_filter_on_the_matching_keys_narrows_both_page_and_total(): void
    {
        $matchingKeys = self::matchingKeys();
        $search = NamedParameter::createForTableColumn(
            $matchingKeys->mainQuery(),
            PostsTableColumns::Post,
            'post 3 %',
            ':search',
        );
        $matchingKeys->mainQuery()->where(sprintf(
            'EXISTS (SELECT 1 FROM %s WHERE %s)',
            PostsTable::name(),
            Composite::and(
                Comparison::equal(PostsTableColumns::UserId->column(), UsersTableColumns::Id->column()),
                new ILike(PostsTableColumns::Post->column(), $search),
            )->toSQL(),
        ));

        $pager = self::createPager($matchingKeys, new Pagination(1, 1));
        $rows = values($pager);

        self::assertSame(['user4'], self::usernames($pager));
        self::assertSame(2, $pager->totalResults());
        self::assertSame(2, $pager->totalPages());
        self::assertSame(['post 1 of user 4', 'post 2 of user 4', 'post 3 of user 4'], self::posts($rows[0]));
    }

    /**
     * A condition on the projection's left join limits which related rows are aggregated without dropping a
     * key, so a user without a match keeps its place on the page and in the total. Its parameter is bound
     * on the projection, which reaches the statement because `execute()` merges the main query's parameters
     * next to those of the CTEs.
     */
    #[Test]
    public function a_condition_on_the_projection_join_limits_the_aggregate_but_keeps_every_key(): void
    {
        $pager = self::createPager(
            self::matchingKeys(),
            new Pagination(1, 3),
            projection: self::projection(
                static fn (QueryBuilder $query): ILike => new ILike(
                    PostsTableColumns::Post->column(),
                    NamedParameter::createForTableColumn($query, PostsTableColumns::Post, 'post 2 %', ':post'),
                ),
            ),
        );
        $rows = values($pager);

        self::assertSame(['user1', 'user2', 'user3'], self::usernames($pager));
        self::assertSame(6, $pager->totalResults());
        self::assertSame([], self::posts($rows[0]));
        self::assertSame([], self::posts($rows[1]));
        self::assertSame(['post 2 of user 3'], self::posts($rows[2]));
    }

    /**
     * The order is applied to the outer query too, not only to the narrow one: a join does not preserve
     * its input's order, so reading a deep page back in the requested direction is what pins the second
     * application. An exact-set assertion cannot see a missing outer `ORDER BY`; an exact sequence can.
     */
    #[Test]
    #[TestWith([1, OrderBy::ASC, ['user1', 'user2', 'user3']])]
    #[TestWith([2, OrderBy::ASC, ['user4', 'user5', 'user6']])]
    #[TestWith([1, OrderBy::DESC, ['user6', 'user5', 'user4']])]
    #[TestWith([2, OrderBy::DESC, ['user3', 'user2', 'user1']])]
    public function it_orders_the_projected_page(int $page, string $direction, array $expected): void
    {
        $pager = self::pager(
            new Pagination($page, 3),
            new OrderBy(OrderBy::field(UsersTableColumns::Username->column(), $direction)),
        );

        self::assertSame($expected, self::usernames($pager));
    }

    #[Test]
    public function it_can_deal_with_empty_resultset(): void
    {
        $composite = self::matchingKeys();
        $composite->mainQuery()->where('false');

        $pager = self::createPager($composite, new Pagination(2, 3));

        self::assertSame([], self::usernames($pager));
        self::assertSame(0, $pager->totalResults());
        self::assertSame(1, $pager->totalPages());
    }

    /**
     * A page past the end is the empty-result shape as well: `total_results` is absent from an empty
     * result, so the total falls back to zero and the page count to one, exactly as `WindowCountPager`.
     */
    #[Test]
    public function a_page_past_the_end_is_empty_and_reports_one_page(): void
    {
        $pager = self::pager(new Pagination(9, 2));

        self::assertSame([], self::usernames($pager));
        self::assertSame(0, $pager->totalResults());
        self::assertSame(1, $pager->totalPages());
    }

    /**
     * Rows come back verbatim, the count field included under the name it was given: every mapper
     * tolerates the extra key, and stripping it would cost an array copy per row for nothing.
     */
    #[Test]
    public function it_yields_the_count_field_on_every_row_under_the_name_it_was_given(): void
    {
        $rows = values(self::pager(new Pagination(1, 2), countField: 'matching_rows'));

        self::assertCount(2, $rows);
        foreach ($rows as $row) {
            self::assertSame(6, (int) $row['matching_rows']);
            self::assertArrayNotHasKey('total_results', $row);
        }
    }

    #[Test]
    public function it_traverses_with_a_mapper(): void
    {
        $pager = self::pager(new Pagination(1, 2));

        self::assertSame(
            ['user1', 'user2'],
            values($pager->traverse(
                static fn (array $row): string => (string) $row[UsersTableColumns::Username->value],
            )),
        );
    }

    /**
     * The iterator is cached, so reading the total and then the rows runs one statement and iterating
     * twice yields the same page rather than an empty second pass. A user inserted between the two passes
     * would change both the total and the first page, so its absence from the second pass is what proves
     * no second statement ran.
     */
    #[Test]
    public function it_is_re_iterable_without_running_a_second_query(): void
    {
        $pager = self::pager(new Pagination(1, 2));

        self::assertSame(['user1', 'user2'], self::usernames($pager));
        self::assertSame(6, $pager->totalResults());

        TransactionManager::instance()->createSavepoint('extra_user');

        try {
            self::connection()->insert(UsersTable::name(), [
                UsersTableColumns::Id->value => Uuid::generate()->value,
                UsersTableColumns::Username->value => 'user0',
                UsersTableColumns::FirstName->value => 'odd',
                UsersTableColumns::LastName->value => 'er0',
            ]);

            self::assertSame(['user1', 'user2'], self::usernames($pager));
            self::assertSame(6, $pager->totalResults());
            self::assertSame(['user0', 'user1'], self::usernames(self::pager(new Pagination(1, 2))));
        } finally {
            TransactionManager::instance()->rollbackSavepoint('extra_user');
        }
    }

    #[Test]
    public function it_does_not_alter_the_provided_query(): void
    {
        $composite = self::matchingKeys();
        $originalSql = $composite->toSQL();

        self::createPager($composite, new Pagination(2, 2))->totalResults();

        self::assertSame($originalSql, $composite->toSQL());
        self::assertSame([], $composite->mainQuery()->getParameters());
    }

    /**
     * A key with no table cannot yield a join condition, so it is refused rather than silently producing
     * a cartesian product.
     */
    #[Test]
    public function it_refuses_a_key_that_is_not_table_qualified(): void
    {
        $this->expectExceptionMessage('The page key must be table qualified');

        DeferredProjectionPager::create(
            new Pagination(1, 2),
            self::matchingKeys(),
            UsersTableColumns::Id->column()->from(null),
            self::defaultOrder(),
            self::projection(),
        );
    }

    /**
     * @param non-empty-string $countField
     */
    private static function pager(
        Pagination $pagination,
        ?OrderBy $order = null,
        string $countField = 'total_results',
    ): DeferredProjectionPager {
        return self::createPager(self::matchingKeys(), $pagination, $order, $countField);
    }

    /**
     * @param non-empty-string $countField
     */
    private static function createPager(
        CompositeQuery $matchingKeys,
        Pagination $pagination,
        ?OrderBy $order = null,
        string $countField = 'total_results',
        ?\Closure $projection = null,
    ): DeferredProjectionPager {
        return DeferredProjectionPager::create(
            pagination: $pagination,
            matchingKeys: $matchingKeys,
            key: UsersTableColumns::Id->column(),
            order: $order ?? self::defaultOrder(),
            projection: $projection ?? self::projection(),
            countField: $countField,
        );
    }

    private static function defaultOrder(): OrderBy
    {
        return new OrderBy(OrderBy::field(UsersTableColumns::Username->column(), OrderBy::ASC));
    }

    /**
     * The narrow half: one column and the filters, with no join and no projection.
     */
    private static function matchingKeys(): CompositeQuery
    {
        return new CompositeQuery(
            self::connection(),
            self::connection()->createQueryBuilder()
                ->select(UsersTableColumns::Id->select())
                ->from(UsersTable::name()),
            [],
        );
    }

    /**
     * A fat projection standing in for a details builder: it is built from scratch and knows nothing about
     * the page CTE, since the pager owns the join onto it.
     *
     * It aggregates, and therefore groups, without knowing the pager's count field: the pager reads the count
     * as a scalar sub-query precisely so that an aggregating projection needs no group-by of its own for it.
     *
     * @param (\Closure(QueryBuilder): Expression)|null $postsCondition narrows the posts it aggregates
     *
     * @return \Closure(CompositeQuery, non-empty-string): QueryBuilder
     */
    private static function projection(?\Closure $postsCondition = null): \Closure
    {
        return static function (CompositeQuery $folded, string $pageAlias) use ($postsCondition): QueryBuilder {
            $query = self::connection()->createQueryBuilder();
            $join = UsersTable::joinOntoPosts();
            if (null !== $postsCondition) {
                $join['condition'] = Composite::and(
                    new SqlExpression($join['condition']),
                    $postsCondition($query),
                )->toSQL();
            }

            return $query->select(
                ...UsersTable::columns()->select(),
                ...[new Alias(
                    JsonbAggStrict::onManyLeftJoinedJsonObjects(
                        new JsonbBuildObject([
                            'id' => PostsTableColumns::Id->column(),
                            'post' => PostsTableColumns::Post->column(),
                        ]),
                        PostsTableColumns::Id->column(),
                        new OrderBy(OrderBy::field(PostsTableColumns::Post->column(), OrderBy::ASC)),
                    ),
                    'posts',
                )->toSQL()],
            )
                ->from(UsersTable::name())
                ->leftJoin(...$join)
                ->groupBy(UsersTableColumns::Id->use());
        };
    }

    /**
     * @return list<string>
     */
    private static function usernames(DeferredProjectionPager $pager): array
    {
        return map(
            values($pager),
            static fn (array $row): string => (string) $row[UsersTableColumns::Username->value],
        );
    }

    /**
     * @return list<string>
     */
    private static function posts(array $row): array
    {
        /** @var list<array{post: string}> $posts */
        $posts = decode((string) $row['posts']);

        return map($posts, static fn (array $post): string => $post['post']);
    }
}
