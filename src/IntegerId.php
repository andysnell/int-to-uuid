<?php

declare(strict_types=1);

namespace WickedByte\IntToUuid;

/**
 * Bidirectionally encodes a 64-bit unsigned integer into a valid RFC4122 Version 8 UUID,
 * within a 32-bit namespace, generated with a 32-bit seed value. The
 * seed value also functions as a validation checksum when attempting to decode a UUID back
 * into an integer ID.
 */
final readonly class IntegerId
{
    public const int ID_MIN = 0;

    public const int ID_MAX = \PHP_INT_MAX;

    public const int NAMESPACE_MIN = 0;

    public const int NAMESPACE_MAX = 4_294_967_295;

    private const string ERROR_TEMPLATE = '%s Must Be an Integer Between %s and %s, inclusive. Got %s';

    /**
     * @phpstan-assert int<0,max> $value
     * @phpstan-assert int<0,4294967295> $namespace
     */
    public function __construct(
        public int $value,
        public int $namespace = self::ID_MIN,
    ) {
        if ($this->value < self::ID_MIN) {
            throw new \UnexpectedValueException(\vsprintf(self::ERROR_TEMPLATE, [
                'Value',
                self::ID_MIN,
                self::ID_MAX,
                $this->value,
            ]));
        }

        if ($this->namespace < self::NAMESPACE_MIN || $this->namespace > self::NAMESPACE_MAX) {
            throw new \UnexpectedValueException(\vsprintf(self::ERROR_TEMPLATE, [
                'Namespace',
                self::NAMESPACE_MIN,
                self::NAMESPACE_MAX,
                $this->namespace,
            ]));
        }
    }

    public static function make(int $value, int $namespace = self::ID_MIN): self
    {
        return new self($value, $namespace);
    }
}
