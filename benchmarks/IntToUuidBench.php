<?php

declare(strict_types=1);

namespace WickedByte\Benchmarks\IntToUuid;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use Ramsey\Uuid\UuidInterface;
use WickedByte\IntToUuid\IntegerId;
use WickedByte\IntToUuid\IntToUuid;

#[Revs(100_000)]
#[Iterations(5)]
class IntToUuidBench
{
    private UuidInterface $uuid;

    private IntegerId $id;

    #[Subject]
    public function encode(): void
    {
        IntToUuid::encode(IntegerId::make(12345, 1155));
    }

    #[Subject]
    #[BeforeMethods('setUpDecode')]
    public function decode(): void
    {
        IntToUuid::decode($this->uuid);
    }

    public function setUpDecode(): void
    {
        $this->uuid = IntToUuid::encode(IntegerId::make(12345, 1155));
    }

    #[Subject]
    #[BeforeMethods('setUpEncodeAndDecode')]
    public function encodeAndDecode(): void
    {
        $uuid = IntToUuid::encode($this->id);
        $id = IntToUuid::decode($uuid);
        \assert($this->id == $id);
    }

    public function setUpEncodeAndDecode(): void
    {
        $this->id = IntegerId::make(
            \random_int(IntegerId::ID_MIN, IntegerId::ID_MAX),
            \random_int(IntegerId::NAMESPACE_MIN, IntegerId::NAMESPACE_MAX),
        );
    }
}
