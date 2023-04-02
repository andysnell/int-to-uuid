<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\IntToUuid;

use Generator;
use InvalidArgumentException;
use LogicException;
use PhoneBurner\IntToUuid\IntegerId;
use PhoneBurner\IntToUuid\IntToUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\Fields;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[CoversClass(IntToUuid::class)]
class IntToUuidTest extends TestCase
{
    #[Test]
    #[DataProvider('providesIntegerAndNamespaceIds')]
    public function it_can_encode_and_decode_64bit_int_into_UUID(int $id, int $namespace): void
    {
        $id = IntegerId::make($id, $namespace);

        $uuid = IntToUuid::encode($id);

        self::assertMatchesRegularExpression(IntToUuid::VALIDATION_REGEX, (string)$uuid);
        self::assertTrue(Uuid::isValid($uuid->toString()));

        $fields = $uuid->getFields();
        self::assertInstanceOf(Fields::class, $fields);
        self::assertSame(IntToUuid::RFC4122_VERSION, $fields->getVersion());
        self::assertSame(IntToUuid::RFC4122_VARIANT, $fields->getVariant());

        self::assertEquals($id, IntToUuid::decode($uuid));
    }

    public static function providesIntegerAndNamespaceIds(): Generator
    {
        for ($i = 0; $i < 1000; ++$i) {
            yield [
                \random_int(IntegerId::ID_MIN, IntegerId::ID_MAX),
                \random_int(IntegerId::NAMESPACE_MIN, IntegerId::NAMESPACE_MAX),
            ];
        }

        yield [IntegerId::ID_MIN, IntegerId::NAMESPACE_MIN];
        yield [IntegerId::ID_MIN, IntegerId::NAMESPACE_MAX];
        yield [IntegerId::ID_MAX, IntegerId::NAMESPACE_MIN];
        yield [IntegerId::ID_MAX, IntegerId::NAMESPACE_MAX];
    }

    #[Test]
    #[DataProvider('provides_valid_uuids_without_encoded_id')]
    public function decode_validates_checksum_value(UuidInterface $bad_uuid): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("UUID Could Not Be Decoded Successfully");
        IntToUuid::decode($bad_uuid);
    }

    public static function provides_valid_uuids_without_encoded_id(): Generator
    {
        yield [Uuid::fromString('4b9a188c-945a-8628-8599-b5b5e604d144')];
        yield [Uuid::fromString('489a188c-945a-8728-8599-b5b5e604d144')];
        yield [Uuid::fromString('489a188c-945a-8628-85a9-b5b5e604d144')];
        yield [Uuid::fromString('489a188c-945a-8628-8599-b5b5e604da44')];
        yield [Uuid::fromString('6359de08-baf9-8060-85e0-90fe7ee66b40')];
        yield [Uuid::fromString('f218614f-a95f-8d78-a74d-653fe5092b74')];
        yield [Uuid::fromString('0276bc60-a49e-888a-ac70-da3f3cedb903')];
        yield [Uuid::fromString('3a282590-fd57-8492-8de8-52eb6fbbc05a')];
        yield [Uuid::fromString('10efb154-0d23-8794-8841-84a966631e8f')];
        yield [Uuid::fromString('772e5800-40be-84d4-9567-09899746d872')];
    }

    #[Test]
    #[DataProvider('provides_invalid_v8_uuids')]
    public function decode_validates_uuid_value(UuidInterface $invalid_uuid): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UUID Does Not Match Required RFC4122 v8 Format');
        IntToUuid::decode($invalid_uuid);
    }

    public static function provides_invalid_v8_uuids(): Generator
    {
        yield [Uuid::fromString(Uuid::NIL)];
        yield [Uuid::fromString(Uuid::MAX)];
        yield [Uuid::fromString('489a188c-945a-4628-8599-b5b5e604d144')];
        yield [Uuid::fromString('6359de08-baf9-4060-85e0-90fe7ee66b40')];
        yield [Uuid::fromString('f218614f-a95f-4d78-a74d-653fe5092b74')];
        yield [Uuid::fromString('0276bc60-a49e-488a-ac70-da3f3cedb903')];
    }
}
