<?php

declare(strict_types=1);

namespace WickedByte\Tests\IntToUuid;

use Generator;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\Fields;
use Ramsey\Uuid\Uuid;
use WickedByte\IntToUuid\IntegerId;
use WickedByte\IntToUuid\IntToUuid;
use WickedByte\Tests\IntToUuid\Fixtures\StringWrapper;

#[CoversClass(IntToUuid::class)]
final class IntToUuidTest extends TestCase
{
    private const array VALID_UUIDS_NOT_V8 = [
        Uuid::NIL,
        Uuid::MAX,
        '489a188c-945a-4628-8599-b5b5e604d144',
        '6359de08-baf9-4060-85e0-90fe7ee66b40',
        'f218614f-a95f-4d78-a74d-653fe5092b74',
        '0276bc60-a49e-488a-ac70-da3f3cedb903',
    ];

    private const array VALID_V8_UUIDS_WITHOUT_ENCODED_ID = [
        '4b9a188c-945a-8628-8599-b5b5e604d144',
        '489a188c-945a-8728-8599-b5b5e604d144',
        '489a188c-945a-8628-85a9-b5b5e604d144',
        '489a188c-945a-8628-8599-b5b5e604da44',
        '6359de08-baf9-8060-85e0-90fe7ee66b40',
        'f218614f-a95f-8d78-a74d-653fe5092b74',
        '0276bc60-a49e-888a-ac70-da3f3cedb903',
        '3a282590-fd57-8492-8de8-52eb6fbbc05a',
        '10efb154-0d23-8794-8841-84a966631e8f',
        '772e5800-40be-84d4-9567-09899746d872',
    ];

    #[Test]
    #[DataProvider('providesIntegerAndNamespaceIds')]
    public function itCanEncodeAndDecode64BitIntIntoUuid(int $id, int $namespace): void
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
        self::assertEquals($id, IntToUuid::decode($uuid->toString()));
        self::assertEquals($id, IntToUuid::decode(new StringWrapper($uuid->toString())));
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
    #[DataProvider('providesValidUuidsWithoutEncodedId')]
    public function decodeValidatesChecksumValue(\Stringable|string $bad_uuid): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("UUID Could Not Be Decoded Successfully");
        IntToUuid::decode($bad_uuid);
    }

    #[Test]
    #[DataProvider('providesInvalidV8Uuids')]
    public function decodeValidatesUuidValue(\Stringable|string $invalid_uuid): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('UUID Does Not Match Required RFC4122 v8 Format');
        IntToUuid::decode($invalid_uuid);
    }

    public static function providesValidUuidsWithoutEncodedId(): Generator
    {
        foreach (self::VALID_V8_UUIDS_WITHOUT_ENCODED_ID as $uuid) {
            yield [Uuid::fromString($uuid)];
            yield [$uuid];
            yield [new StringWrapper($uuid)];
        }
    }

    public static function providesInvalidV8Uuids(): Generator
    {
        foreach (self::VALID_UUIDS_NOT_V8 as $uuid) {
            yield [Uuid::fromString($uuid)];
            yield [$uuid];
            yield [new StringWrapper($uuid)];
        }
    }
}
