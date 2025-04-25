<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\DBAL\Type;

use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

abstract class DbalTypeWithFailingCasesTestCase extends DbalTypeTestCase
{
    abstract public static function provideInvalidConvertToPHPValueCases(): iterable;

    abstract public static function provideInvalidConvertToDbValueCases(): iterable;

    #[Test]
    #[DataProvider('provideInvalidConvertToPHPValueCases')]
    public function it_fails_converting_to_db(mixed $value): void
    {
        $type = $this->getType();
        $platform = $this->getDatabasePlatform();

        $this->expectException(InvalidType::class);
        $type->convertToDatabaseValue($value, $platform);
    }

    #[Test]
    #[DataProvider('provideInvalidConvertToDbValueCases')]
    public function it_fails_converting_to_php(mixed $value): void
    {
        $type = $this->getType();
        $platform = $this->getDatabasePlatform();

        $this->expectException(InvalidFormat::class);
        $type->convertToPHPValue($value, $platform);
    }
}
