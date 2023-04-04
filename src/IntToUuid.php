<?php

declare(strict_types=1);

namespace PhoneBurner\IntToUuid;

use InvalidArgumentException;
use LogicException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class IntToUuid
{
    public const VALIDATION_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-8[0-9a-f]{3}-[ab89][0-9a-f]{3}-[0-9a-f]{12}$/';

    public const RFC4122_VERSION = Uuid::UUID_TYPE_CUSTOM;

    public const RFC4122_VARIANT = Uuid::RFC_4122;

    private const INT64_UNSIGNED_BE = 'J';

    private const INT32_UNSIGNED_BE = 'N';

    public static function encode(IntegerId $integer_id): UuidInterface
    {
        $id = \pack(self::INT64_UNSIGNED_BE, $integer_id->value);
        $namespace = \pack(self::INT32_UNSIGNED_BE, $integer_id->namespace);
        $seed = self::seed($id, $namespace);

        $id ^= self::hash($namespace . $seed);
        $namespace ^= self::hash($seed);

        return Uuid::fromBytes($namespace . \substr($id, 0, 2) . $seed . \substr($id, 2));
    }

    public static function decode(UuidInterface $uuid): IntegerId
    {
        if (! \preg_match(self::VALIDATION_REGEX, $uuid->toString())) {
            throw new InvalidArgumentException('UUID Does Not Match Required RFC4122 v8 Format');
        }

        $bytes = $uuid->getBytes();

        $seed = \substr($bytes, 6, 4);
        $namespace = \substr($bytes, 0, 4);
        $id = \substr($bytes, 4, 2) . \substr($bytes, 10);

        $namespace ^= self::hash($seed);
        $id ^= self::hash($namespace . $seed);

        if (self::seed($id, $namespace) !== $seed) {
            throw new LogicException("UUID Could Not Be Decoded Successfully");
        }

        return IntegerId::make(self::unpackInt64($id), self::unpackInt32($namespace));
    }

    private static function hash(string $message): string
    {
        return \hash('xxh3', $message, true);
    }

    private static function seed(string $packed_id, string $packed_namespace): string
    {
        $hash = self::hash($packed_id . $packed_namespace);
        $seed = self::unpackInt32(\substr($hash, 0, 4));
        return \pack(self::INT32_UNSIGNED_BE, $seed & 0x0FFF3FFF | 0x80008000);
    }

    private static function unpackInt32(string $string): int
    {
        $data = (array)\unpack(self::INT32_UNSIGNED_BE, $string);
        return $data[1] ?? throw new LogicException('UUID Unpack Error');
    }

    private static function unpackInt64(string $string): int
    {
        $data = (array)\unpack(self::INT64_UNSIGNED_BE, $string);
        return $data[1] ?? throw new LogicException('UUID Unpack Error');
    }
}
