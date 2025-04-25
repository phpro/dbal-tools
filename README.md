[![Installs](https://img.shields.io/packagist/dt/phpro/dbal-tools.svg)](https://packagist.org/packages/phpro/dbal-tools/stats)
[![Packagist](https://img.shields.io/packagist/v/phpro/dbal-tools.svg)](https://packagist.org/packages/phpro/api-problem-bundle)


# DBAL Tools

This package provides a set of tools to work with the Doctrine DBAL.

## Installation

```sh
composer require phpro/dbal-tools
```

The package can be used standalone or with Symfony.
If you are not using `symfony/flex`, you'll have to manually add the bundle to your bundles file:

```php
// config/bundles.php

return [
    // ...
    Phpro\DbalTools\DbalToolsBundle::class => ['all' => true],
];
```

## Schema

This package contains a set of schema tools to configure your database schema from within PHP.
You can configure following schema types:

### Configuring a table

A table consists out of 2 parts:

- The class that declared the table
- An enum that declares the available table columns:

```php
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;

enum UsersTableColumns: string implements TableColumnsInterface
{
    case Id = 'user_id';
    case Username = 'username';

    use TableColumnsTrait;

    public function linkedTableClass(): string
    {
        return UsersTable::class;
    }
}
```

```php
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Schema\Table;

final class UsersTable extends Table
{
    public static function name(): string
    {
        return 'users';
    }

    public static function columns(): Columns
    {
        return Columns::for(UsersTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        $table = new DoctrineTable(self::name());
        $table->addColumn(UsersTableColumns::Id->value, Types::GUID, [
            'notnull' => true,
        ]);
        $table->setPrimaryKey([UsersTableColumns::Id->value]);

        $table->addColumn(UsersTableColumns::Username->value, Types::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);

        return $table;
    }
}
```

You can register this table configuration in your Symfony service configuration:

```yaml
services:
    App\Dbal\Schema\UsersTable:
        tags:
            - 'phpro.dbal_tools.schema.table'
```

This way, it is available inside the `Doctrine\DBAL\Schema\Schema` service and can be used to create the table through migrations.

### Configuring sequences

From time to time, you might need a sequence.
This can be configured in the same way as a table:

```php
use Phpro\DbalTools\Schema\Sequence;
use Doctrine\DBAL\Schema\Sequence as DoctrineSequence;

final class UserNumberSequence extends Sequence
{
    public static function name(): string
    {
        return 'user_number_seq';
    }

    public static function createSequence(): DoctrineSequence
    {
        return new DoctrineSequence(self::name());
    }
}
```

Next, add the sequence to your service configuration:

```yaml
services:
    App\Dbal\Schema\UserNumberSequence:
        tags:
            - 'phpro.dbal_tools.schema.sequence'
```

## Migrations

This package ships with [doctrine/DoctrineMigrationsBundle](https://github.com/doctrine/DoctrineMigrationsBundle).
It links the schema configuration to the migration commands.
This way, you can use doctrine:migrations, just like you would in a regular ORM based system.

```sh
./bin/console doctrine:migrations:diff
./bin/console doctrine:migrations:migrate
```

## Building models and repositories

Since no ORM is being used, the main structure for building models and repositories would follow following pattern:


You can build your entities in raw PHP without any mapping limitations:
(just using public props here for simplicity)

```php
namespace App\Entity;

class User
{
    public function __construct(
        public string $id,
        public string $userName
    ) {
    }
}
```

A repository could look like this:

```php
namespace App\Repository;

use App\Doctrine\Schema\UsersTable;
use App\Doctrine\Schema\UsersTableColumns;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\Factory\NamedParameter;

class UsersRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function findById(string $id): ?User
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select(...UsersTable::columns()->select())
            ->from(UsersTable::name())
            ->where(Comparison::equal(
                UsersTableColumns::Id,
                NamedParameter::createForTableColumn($qb, UsersTableColumns::Id, $id)
            )->toSQL());

        if (!$row = $qb->fetchAssociative()) {
            return null;
        }

        return new User(
            $row[UsersTableColumns::Id->value],
            $row[UsersTableColumns::Username->value],
        );
    }

    public function create(User $user): void
    {
        $this->connection->insert(
            UsersTable::name(),
            [
                UsersTableColumns::Id->value => $user->id,
                UsersTableColumns::Username->value => $user->userName,
            ],
            UsersTable::columnTypes()
        );
    }
}
```

As you can see, you can use the schema configuration to build your queries.
If any of the table names or column names change, you will only have to change it in one place.
In a real application, you can split up a data mapper that maps the entity to the table and back.
That way, you have full control on how the data is made available in the entity.

## Fixtures

This package contains a command to load fixtures.
In order to create a fixture, you need to create a new PHP class per entity:

```php
use App\Doctrine\Schema\UsersTable;
use App\Entity\User;
use App\Repository\UsersRepository;
use Phpro\DbalTools\Fixtures\Fixture;

/**
 * @template-implements Fixture<User>
 */
final readonly class UserFixtures implements Fixture
{
    public function __construct(
        private UsersRepository $userRepository,
    ) {
    }

    public function type(): string
    {
        return User::class;
    }

    public function tables(): array
    {
        return [UsersTable::name()];
    }

    /**
     * @return \Generator<string, User>
     */
    public function execute(): \Generator
    {
        foreach ($this->provideFixtures() as $fixture) {
            if ($this->exists($fixture)) {
                continue;
            }

            yield $fixture->id => $fixture;
            $this->userRepository->create($fixture);
        }
    }

    public function exists(object $x): bool
    {
        return (bool) $this->userRepository->findById($x->id);
    }


    /**
     * @return \Generator<string, User>
     */
    private function provideFixtures(): \Generator
    {
        yield 'admin' => new User(
            '9080f592-3a1e-433d-8b27-0e109fd1d32c',
            'admin',
        );
    }
}
```

Next, you can register the fixture in your service configuration:

```yaml
services:
    App\Doctrine\Fixtures\UserFixtures:
        arguments:
            - '@App\Repository\UsersRepository'
        tags:
            - 'phpro.dbal_tools.fixture'

```

You can now load the fixtures using the command:

```sh
./bin/console doctrine:fixtures
```

Following options are available:

```
--type=TYPE           Only create / truncate specific type (FQCN) "App\Domain\Model\User".
-t, --truncate        Truncate all fixture tables and relations.
-r, --reload          Truncate and Import all fixture.
```

## Testing

This package contains a set of tools that can be used to test your code against a database.
If you are using paratest, the system will automatically create a new database for each process.
This way, tests can independently run in parallel resulting in a super fast test-suite.



## About

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/phpro/dbal-tools/issues).
Please take a look at our rules before [contributing your code](CONTRIBUTING).

### License

api-problem-bundle is licensed under the [MIT License](LICENSE).
