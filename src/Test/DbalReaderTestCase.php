<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test;

use Phpro\DbalTools\Test\Manager\TransactionManager;

/**
 * Usage: When fixtures do not modify between test cases.
 * - Fixtures are only loaded once.
 */
abstract class DbalReaderTestCase extends DbalTestCase
{
    private static bool $hasFixtures = false;
    private static array $fixturesMemoize = [];

    public static function tearDownAfterClass(): void
    {
        TransactionManager::instance()->rollbackSavepoint('blank_fixtures');
        self::$fixturesMemoize = [];
        self::$hasFixtures = false;
    }

    protected function setUp(): void
    {
        if (self::$hasFixtures) {
            $this->fixtures = self::$fixturesMemoize;

            return;
        }

        parent::setUp();

        self::$fixturesMemoize = $this->fixtures;
        self::$hasFixtures = true;
    }

    protected function tearDown(): void
    {
        // disable rollback
    }
}
