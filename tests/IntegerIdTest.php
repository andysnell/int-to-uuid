<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\IntToUuid;

use Generator;
use InvalidArgumentException;
use PhoneBurner\IntToUuid\IntegerId;
use PhoneBurner\IntToUuid\IntToUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IntegerId::class)]
class IntegerIdTest extends TestCase
{
    #[Test]
    #[DataProvider('provides_valid_values_and_namespaces')]
    public function make_instantiates_the_IntegerId(int $value, int $namespace): void
    {
        $integer_id = IntegerId::make($value, $namespace);
        self::assertSame($value, $integer_id->value);
        self::assertSame($namespace, $integer_id->namespace);
    }

    public static function provides_valid_values_and_namespaces(): Generator
    {
        yield [0, 0];
        yield [0, 1];
        yield [1, 0];
        yield [1, 1];
        yield [\PHP_INT_MAX, 2 ** 32 - 1];

        for ($i = 0; $i < 1000; ++$i) {
            yield [
                \random_int(IntegerId::ID_MIN, IntegerId::ID_MAX),
                \random_int(IntegerId::NAMESPACE_MIN, IntegerId::NAMESPACE_MAX),
            ];
        }
    }

    #[Test]
    #[DataProvider('provides_invalid_id_values')]
    public function minimum_id_value_is_checked(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$value Must Be an Integer Between 0 and 9223372036854775807, inclusive.');
        IntToUuid::encode(IntegerId::make($value, 123));
    }

    public static function provides_invalid_id_values(): Generator
    {
        yield [-1];
        yield [\PHP_INT_MIN];
    }

    #[Test]
    #[DataProvider('provides_invalid_namespace_values')]
    public function namespace_boundaries_are_checked(int $namespace): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$namespace Must Be an Integer Between 0 and 4294967295, inclusive.');
        IntegerId::make(1, $namespace);
    }

    public static function provides_invalid_namespace_values(): Generator
    {
        yield [-1];
        yield [2 ** 32];
        yield [\PHP_INT_MIN];
        yield [\PHP_INT_MAX];
    }
}
