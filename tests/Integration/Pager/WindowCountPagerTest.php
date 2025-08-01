<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Pager;

use Phpro\DbalTools\Expression\Count;
use Phpro\DbalTools\Expression\Over;
use Phpro\DbalTools\Expression\PartitionBy;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Pager\Pagination;
use Phpro\DbalTools\Pager\WindowCountPager;
use Phpro\DbalTools\Query\CompositeQuery;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PhproTest\DbalTools\Fixtures\Schema\PostsTable;
use PhproTest\DbalTools\Fixtures\Schema\PostsTableColumns;
use PhproTest\DbalTools\Fixtures\Schema\UsersTable;
use PhproTest\DbalTools\Fixtures\Schema\UsersTableColumns;
use PhproTest\DbalTools\Fixtures\Type\Uuid;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Vec\map;

final class WindowCountPagerTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
        for ($i = 1; $i <= 10; ++$i) {
            self::connection()->insert(UsersTable::name(), $this->fixtures['user'.$i] = [
                UsersTableColumns::Id->value => $userId = Uuid::generate()->value,
                UsersTableColumns::Username->value => 'user'.$i,
                UsersTableColumns::FirstName->value => 'us',
                UsersTableColumns::LastName->value => 'er'.$i,
            ]);

            for ($j = 1; $j <= 3; ++$j) {
                self::connection()->insert(PostsTable::name(), $this->fixtures['user'.$i.'-post'.$j] = [
                    PostsTableColumns::Id->value => Uuid::generate()->value,
                    PostsTableColumns::UserId->value => $userId,
                    PostsTableColumns::Post->value => 'This is post '.$j.' of user '.$i,
                ]);
            }
        }
    }

    protected static function schemaTables(): array
    {
        return [
            UsersTable::class,
            PostsTable::class,
        ];
    }

    private static function assertResultFixtures(
        array $expectedResults,
        int $expectedTotal,
        array $actual,
        string $countField = 'total_results',
    ): void {
        self::assertSame(
            map(
                $expectedResults,
                static fn (array $record) => [...$record, $countField => $expectedTotal],
            ),
            $actual
        );
    }

    #[Test]
    public function it_can_deal_with_empty_resultset(): void
    {
        $query = self::connection()->createQueryBuilder()
            ->select(...UsersTable::columns()->select())
            ->from(UsersTable::name())
            ->where('false');
        $originalSql = $query->getSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
        );

        self::assertSame($originalSql, $query->getSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(0, $pager->totalResults());
        self::assertSame(1, $pager->totalPages());
        self::assertResultFixtures(
            [],
            0,
            [...$pager]
        );
    }

    #[Test]
    public function it_can_deal_with_simple_query(): void
    {
        $grabUserName = static fn (array $item) => $item[UsersTableColumns::Username->value];
        $query = self::connection()->createQueryBuilder()
            ->select(...UsersTable::columns()->select())
            ->from(UsersTable::name());
        $originalSql = $query->getSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
        );

        self::assertSame($originalSql, $query->getSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(10, $pager->totalResults());
        self::assertSame(4, $pager->totalPages());
        self::assertResultFixtures(
            [
                $this->fixtures['user4'],
                $this->fixtures['user5'],
                $this->fixtures['user6'],
            ],
            10,
            [...$pager]
        );
        self::assertSame(['user4', 'user5', 'user6'], $pager->traverse($grabUserName));
    }

    #[Test]
    public function it_can_deal_with_simple_joined_query(): void
    {
        $grabUserName = static fn (array $item) => $item[UsersTableColumns::Username->value];
        $query = self::connection()->createQueryBuilder()
            ->select(...UsersTable::columns()->select())
            ->from(UsersTable::name())
            ->innerJoin(...UsersTable::joinOntoPosts());
        $originalSql = $query->getSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
        );

        self::assertSame($originalSql, $query->getSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(30, $pager->totalResults());
        self::assertSame(10, $pager->totalPages());
        self::assertResultFixtures(
            [
                $this->fixtures['user2'],
                $this->fixtures['user2'],
                $this->fixtures['user2'],
            ],
            30,
            [...$pager]
        );
        self::assertSame(['user2', 'user2', 'user2'], $pager->traverse($grabUserName));
    }

    #[Test]
    public function it_can_deal_with_simple_aggregated_query(): void
    {
        $grabUserName = static fn (array $item) => $item[UsersTableColumns::Username->value];
        $query = self::connection()->createQueryBuilder()
            ->select(...UsersTable::columns()->select(), ...[PostsTableColumns::Id->apply(static fn ($column) => new Count($column), 'post_count')])
            ->from(UsersTable::name())
            ->innerJoin(...UsersTable::joinOntoPosts())
            ->groupBy(UsersTableColumns::Id->use())
            ->orderBy(UsersTableColumns::Username->value);
        $originalSql = $query->getSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
        );

        self::assertSame($originalSql, $query->getSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(10, $pager->totalResults());
        self::assertSame(4, $pager->totalPages());
        self::assertResultFixtures(
            [
                [...$this->fixtures['user3'], 'post_count' => 3],
                [...$this->fixtures['user4'], 'post_count' => 3],
                [...$this->fixtures['user5'], 'post_count' => 3],
            ],
            10,
            [...$pager]
        );
        self::assertSame(['user3', 'user4', 'user5'], $pager->traverse($grabUserName));
    }

    #[Test]
    public function it_can_deal_with_custom_settings(): void
    {
        $grabUserName = static fn (array $item) => $item[UsersTableColumns::Username->value];
        $query = self::connection()->createQueryBuilder()
            ->select(...UsersTable::columns()->select())
            ->from(UsersTable::name())
            ->innerJoin(...UsersTable::joinOntoPosts())
            ->orderBy(UsersTableColumns::Username->value);
        $originalSql = $query->getSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
            countField: 'custom_count_field',
            countExpression: Over::aggregation(
                new Count(SqlExpression::int(1)),
                Over::partition(new PartitionBy(UsersTableColumns::Id)),
            )
        );

        self::assertSame($originalSql, $query->getSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(3, $pager->totalResults());
        self::assertSame(1, $pager->totalPages());
        self::assertResultFixtures(
            [
                $this->fixtures['user10'],
                $this->fixtures['user10'],
                $this->fixtures['user10'],
            ],
            3,
            [...$pager],
            'custom_count_field',
        );
        self::assertSame(['user10', 'user10', 'user10'], $pager->traverse($grabUserName));
    }

    #[Test]
    public function it_can_deal_with_composite_query(): void
    {
        $grabUserName = static fn (array $item) => $item[UsersTableColumns::Username->value];
        $query = CompositeQuery::from(self::connection());
        $query->mainQuery()
            ->select(...UsersTable::columns()->select())
            ->from(UsersTable::name());
        $originalSql = $query->toSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
        );

        self::assertSame($originalSql, $query->toSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(10, $pager->totalResults());
        self::assertSame(4, $pager->totalPages());
        self::assertResultFixtures(
            [
                $this->fixtures['user4'],
                $this->fixtures['user5'],
                $this->fixtures['user6'],
            ],
            10,
            [...$pager]
        );
        self::assertSame(['user4', 'user5', 'user6'], $pager->traverse($grabUserName));
    }

    #[Test]
    public function it_can_deal_with_composite_joined_query(): void
    {
        $grabUserName = static fn (array $item) => $item[UsersTableColumns::Username->value];
        $query = CompositeQuery::from(self::connection());
        $query->mainQuery()
            ->select(...UsersTable::columns()->select())
            ->from(UsersTable::name())
            ->innerJoin(...UsersTable::joinOntoPosts());
        $originalSql = $query->toSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
        );

        self::assertSame($originalSql, $query->toSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(30, $pager->totalResults());
        self::assertSame(10, $pager->totalPages());
        self::assertResultFixtures(
            [
                $this->fixtures['user2'],
                $this->fixtures['user2'],
                $this->fixtures['user2'],
            ],
            30,
            [...$pager]
        );
        self::assertSame(['user2', 'user2', 'user2'], $pager->traverse($grabUserName));
    }

    #[Test]
    public function it_can_deal_with_composite_aggregated_query(): void
    {
        $grabUserName = static fn (array $item) => $item[UsersTableColumns::Username->value];
        $query = CompositeQuery::from(self::connection());
        $query->mainQuery()
            ->select(...UsersTable::columns()->select(), ...[PostsTableColumns::Id->apply(static fn ($column) => new Count($column), 'post_count')])
            ->from(UsersTable::name())
            ->innerJoin(...UsersTable::joinOntoPosts())
            ->groupBy(UsersTableColumns::Id->use())
            ->orderBy(UsersTableColumns::Username->value);
        $originalSql = $query->toSQL();

        $pager = WindowCountPager::create(
            new Pagination(page: 2, limit: 3),
            $query,
        );

        self::assertSame($originalSql, $query->toSQL());
        self::assertSame(2, $pager->pagination()->page);
        self::assertSame(3, $pager->pagination()->limit);
        self::assertSame(10, $pager->totalResults());
        self::assertSame(4, $pager->totalPages());
        self::assertResultFixtures(
            [
                [...$this->fixtures['user3'], 'post_count' => 3],
                [...$this->fixtures['user4'], 'post_count' => 3],
                [...$this->fixtures['user5'], 'post_count' => 3],
            ],
            10,
            [...$pager]
        );
        self::assertSame(['user3', 'user4', 'user5'], $pager->traverse($grabUserName));
    }
}
