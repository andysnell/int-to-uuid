<?php

declare(strict_types=1);

namespace PhoneBurner\IntToUuid;

final readonly class IntegerId
{
    public const ID_MIN = 0;

    public const ID_MAX = \PHP_INT_MAX;

    public const NAMESPACE_MIN = 0;

    public const NAMESPACE_MAX = 2 ** 32 - 1;

    private const ERROR_TEMPLATE = '%s Must Be an Integer Between %s and %s, inclusive. Got %s';

    /**
     * @phpstan-assert int<0,max> $value
     * @phpstan-assert int<0,262143> $namespace
     */
    private function __construct(
        public int $value,
        public int $namespace,
    ) {
        if ($this->value < self::ID_MIN) {
            throw new \InvalidArgumentException(\vsprintf(self::ERROR_TEMPLATE, [
                '$value',
                self::ID_MIN,
                self::ID_MAX,
                $this->value,
            ]));
        }

        if ($this->namespace < self::NAMESPACE_MIN || $this->namespace > self::NAMESPACE_MAX) {
            throw new \InvalidArgumentException(\vsprintf(self::ERROR_TEMPLATE, [
                '$namespace',
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
