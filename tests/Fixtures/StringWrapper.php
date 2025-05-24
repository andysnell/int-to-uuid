<?php

declare(strict_types=1);

namespace WickedByte\Tests\IntToUuid\Fixtures;

final readonly class StringWrapper implements \Stringable
{
    public function __construct(private readonly string $value)
    {
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
