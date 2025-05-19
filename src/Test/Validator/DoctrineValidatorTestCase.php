<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\Validator;

use Doctrine\DBAL\Connection;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\Manager\ConnectionManager;
use Phpro\DbalTools\Test\Manager\SchemaManager;
use Phpro\DbalTools\Test\Manager\TransactionManager;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @template T of ConstraintValidatorInterface
 * @template-extends ConstraintValidatorTestCase<T>
 */
abstract class DoctrineValidatorTestCase extends ConstraintValidatorTestCase
{
    /**
     * @return list<class-string<Table>>
     */
    abstract protected static function schemaTables(): array;

    public static function setUpBeforeClass(): void
    {
        static::overrideDbalTypes();
        SchemaManager::instance()->createTables(static::schemaTables());
    }

    abstract protected function createFixtures(): void;

    /**
     * In this method, you can override or add dbal types.
     */
    protected static function overrideDbalTypes(): void
    {
    }

    protected static function connection(): Connection
    {
        return ConnectionManager::getConnection();
    }

    protected function setUp(): void
    {
        parent::setUp();
        TransactionManager::instance()->createSavepoint('validator_fixtures');
        $this->createFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TransactionManager::instance()->rollbackSavepoint('validator_fixtures');
    }
}
