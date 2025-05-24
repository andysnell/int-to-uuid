<?php

declare(strict_types=1);

namespace WickedByte\Tests\IntToUuid;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WickedByte\IntToUuid\IntegerId;
use WickedByte\IntToUuid\IntToUuid;

#[CoversClass(IntegerId::class)]
final class IntegerIdTest extends TestCase
{
    #[Test]
    #[DataProvider('providesValidValuesAndNamespaces')]
    public function makeInstantiatesTheIntegerId(int $value, int $namespace): void
    {
        $integer_id = IntegerId::make($value, $namespace);
        self::assertSame($value, $integer_id->value);
        self::assertSame($namespace, $integer_id->namespace);
    }

    public static function providesValidValuesAndNamespaces(): Generator
    {
        yield [0, 0];
        yield [0, 1];
        yield [1, 0];
        yield [1, 1];
        yield [IntegerId::ID_MIN, IntegerId::NAMESPACE_MIN];
        yield [IntegerId::ID_MAX, IntegerId::NAMESPACE_MAX];

        for ($i = 0; $i < 1000; ++$i) {
            yield [
                \random_int(IntegerId::ID_MIN, IntegerId::ID_MAX),
                \random_int(IntegerId::NAMESPACE_MIN, IntegerId::NAMESPACE_MAX),
            ];
        }
    }

    #[Test]
    #[DataProvider('providesInvalidIdValues')]
    public function minimumIdValueIsChecked(int $value): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value Must Be an Integer Between 0 and 9223372036854775807, inclusive.');
        IntToUuid::encode(IntegerId::make($value, 123));
    }

    public static function providesInvalidIdValues(): Generator
    {
        yield [-1];
        yield [\PHP_INT_MIN];
    }

    #[Test]
    #[DataProvider('providesInvalidNamespaceValues')]
    public function namespaceBoundariesAreChecked(int $namespace): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Namespace Must Be an Integer Between 0 and 4294967295, inclusive.');
        IntegerId::make(1, $namespace);
    }

    public static function providesInvalidNamespaceValues(): Generator
    {
        yield [-1];
        yield [2 ** 32];
        yield [\PHP_INT_MIN];
        yield [\PHP_INT_MAX];
    }
}
