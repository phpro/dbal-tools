<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Schema;

use Doctrine\DBAL\Schema\Sequence as DoctrineSequence;

/**
 * @codeCoverageIgnore The abstract class is implemented by concrete test-cases and classes.
 */
abstract class Sequence
{
    /**
     * @return non-empty-string
     */
    abstract public static function name(): string;

    abstract public static function createSequence(): DoctrineSequence;
}
