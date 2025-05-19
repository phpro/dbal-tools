[![Installs](https://img.shields.io/packagist/dt/phpro/dbal-tools.svg)](https://packagist.org/packages/phpro/dbal-tools/stats)
[![Packagist](https://img.shields.io/packagist/v/phpro/dbal-tools.svg)](https://packagist.org/packages/phpro/dbal-tools)


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

If you want to make sure that all columns are being used inside the table, you can add a PHPUnit `TableColumnEnumTestCase` for the implementation:

```php
<?php

use Phpro\DbalTools\Test\Column\TableColumnEnumTestCase;

final class UsersTableColumnsTest extends TableColumnEnumTestCase
{
    public function className(): string
    {
        return UsersTableColumns::class;
    }
}
```

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

### The DbalTestCase class

If you want to test a class that executes database queries, you can use the `DbalTestCase` class.
This class provides tools to build up the database schema, load testing fixtures and assert if database records exist.
An example on how to test the repository we created above:

```php
use App\Doctrine\Schema\UsersTable;
use App\Doctrine\Schema\UsersTableColumns;
use App\Entity\User;
use App\Repository\UsersRepository;
use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\Composite;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Test\DbalTestCase;
use PHPUnit\Framework\Attributes\Test;

final class UsersRepositoryTest extends DbalTestCase
{
    protected function createFixtures(): void
    {
        self::insert(
            UsersTable::name(),
            UsersTable::columnTypes(),
            $this->fixtures['user1'] = [
                UsersTableColumns::Id->value => '07220bfb-00ff-4f69-9543-bb7a959ad452',
                UsersTableColumns::Username->value => 'user1',
            ],
        );
    }

    protected static function schemaTables(): array
    {
        return [UsersTable::class];
    }

    #[Test]
    public function it_can_fetch_user_by_id(): void
    {
        $repository = $this->createRepository();
        $user1 = $repository->findById($this->fixtures['user1'][UsersTableColumns::Id->value]);

        self::assertSame($this->fixtures['user1'][UsersTableColumns::Id->value], $user1->id);
        self::assertSame($this->fixtures['user1'][UsersTableColumns::Username->value], $user1->userName);
    }

    #[Test]
    public function it_can_create_user(): void
    {
        $repository = $this->createRepository();
        $repository->create(new User('dea2303c-0224-4765-9712-7847a49d8eb7', 'user2'));

        self::assertRecordExists(UsersTable::name(), Composite::and(
            Comparison::equal(UsersTableColumns::Id, new LiteralString('dea2303c-0224-4765-9712-7847a49d8eb7')),
            Comparison::equal(UsersTableColumns::Username, new LiteralString('user2')),
        ));
    }

    private function createRepository(): UsersRepository
    {
        return new UsersRepository($this->connection());
    }
}
```

### The DbalReaderTestCase class

If you have a class that performs a very specific database lookup, you can use the `DbalReaderTestCase` class.
This class will only load the fixtures that are needed for the test once instead of before each test, resulting in a faster test suite.

Example:

```php
use App\Doctrine\Schema\UsersTable;
use App\Doctrine\Schema\UsersTableColumns;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class UsersLookupTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
        self::insert(
            UsersTable::name(),
            UsersTable::columnTypes(),
            $this->fixtures['user1'] = [
                UsersTableColumns::Id->value => '07220bfb-00ff-4f69-9543-bb7a959ad452',
                UsersTableColumns::Username->value => 'user1',
                UsersTableColumns::Active->value => true,
            ],
            $this->fixtures['user2'] = [
                UsersTableColumns::Id->value => '503beea0-1b37-4303-b8d7-e23f16db0ed6',
                UsersTableColumns::Username->value => 'user1',
                UsersTableColumns::Active->value => false,
            ],
        );
    }

    protected static function schemaTables(): array
    {
        return [UsersTable::class];
    }

    #[Test]
    public function it_can_find_active_users(): void
    {
        $queryHandler = new FindActiveUsersHandler($this->connection());
        $users = $queryHandler->handle(new ActiveUsersQuery(isActive: true));

        self::assertCount(1, $users);
        self::assertSame($this->fixtures['user1'][UsersTableColumns::Id->value], $users[0]->id);
        
    }

    #[Test]
    public function it_can_find_inactive_users(): void
    {
        $queryHandler = new FindActiveUsersHandler($this->connection());
        $users = $queryHandler->handle(new ActiveUsersQuery(isActive: false));

        self::assertCount(1, $users);
        self::assertSame($this->fixtures['user2'][UsersTableColumns::Id->value], $users[0]->id);
    }
}
```

### The DoctrineValidatorTestCase class

If you are using `symfony/validator`, you can use the `DoctrineValidatorTestCase` class to test your validation rules.
This class will use symfony's `ConstraintValidatorTestCase` as a base and will automatically load the database schema and fixtures.
This way, you can test your validation rules against database records.

```php
use Phpro\DbalTools\Test\Validator\DoctrineValidatorTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\ConstraintValidatorInterface;

final class UniqueUsernameValidatorTest extends DoctrineValidatorTestCase
{
    protected static function schemaTables(): array
    {
        return [UsersTable::class];
    }

    protected function createFixtures(): void
    {
        self::connection()->insert(
            UsersTable::name(),
            [
                UsersTableColumns::Id->value => '2c8c206c-0031-4c85-b36d-a7d5be16c138',
                UsersTableColumns::Username->value => 'noobslayer23',
            ],
            UsersTable::columnTypes(),
        );
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new UniqueUsernameValidator(
            self::connection()
        );
    }

    #[Test]
    public function it_must_have_a_unique_username(): void
    {
        $constraint = new UniqueUsernameConstraint();
        $this->validator->validate(new User('f7e2c2df-1786-497b-b71e-43b66073ecc6', 'noobslayer23'), $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'There is already a user with the username {{ username }}. Please choose another username.',
            $violationList->get(0)->getMessage()
        );
    }
}
```

## Validators

This package contains a set of common validators that can be used to validate your data against the database.

### SchemaFieldValidator

This validator can be used to validate if a field exists in the database schema.
It will perform checks like: Does the provided input length exceed the length of the column in the database schema.

Example configuration:

```yaml
App\Domain\Model\User:
  properties:
    userName:
      - Phpro\DbalTools\Validator\SchemaFieldConstraint:
          table: App\Infrastructure\Doctrine\Schema\User\UsersTable
          column: !php/enum App\Infrastructure\Doctrine\Schema\User\UsersTableColumns::Username
```

### TableKeyExistsValidator

This validator can be used to validate if a record identified by a key exists in the database.
It can be used to verify if a relation ID exists before storing it in the database.

Example configuration:

```yaml
App\Domain\Model\User:
  properties:
    companyId:
      - Phpro\DbalTools\Validator\TableKeyExistsConstraint:
          table: App\Infrastructure\Doctrine\Schema\Company\CompaniesTable
          column: !php/enum App\Infrastructure\Doctrine\Schema\Company\CompaniesTableColumns::Id
```

### UniqueValidator

This validator can be used to validate if a record with the same value exists in the database already to ensure uniqueness.

Example configuration:

```yaml
App\Domain\Model\User:
  properties:
    userName:
      - Phpro\DbalTools\Validator\UniqueConstraint:
            columns:
              "username": !php/enum App\Infrastructure\Doctrine\Schema\User\UsersTableColumns::Username
          
            # You can specify an alternate message and path name.
            message: "A user already exists with this username."
            path: "data.username"
            
            # Can be used for updates to check if you are updating the existing record:
            identifiers:
              "id": !php/enum App\Infrastructure\Doctrine\Schema\User\UsersTableColumns::Id
```

## About

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/phpro/dbal-tools/issues).
Please take a look at our rules before [contributing your code](CONTRIBUTING).

### License

dbal-tools is licensed under the [MIT License](LICENSE).
