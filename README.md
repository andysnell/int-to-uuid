# Integer ID To RFC 4122 UUID Converter

Bidirectionally encodes a non-negative 64-bit unsigned "id" integer and optional
32-bit "namespace" integer into a valid
[RFC 4122](https://www.rfc-editor.org/rfc/rfc4122) UUID, with the internet-draft
[Version 8](https://datatracker.ietf.org/doc/html/draft-ietf-uuidrev-rfc4122bis-00#section-5.8)
format. The id and namespace integers are encoded to obscure their value and
produce non-sequential UUIDs, while guaranteeing uniqueness and reproducibility.

This could be used to present an auto-incrementing integer "database id" as a
UUID (proxy ID) in a public context, where you would not want to expose an
enumerable, sequential value directly tied to your database structure/data.
Since the encoded UUID can be converted back into integer namespace and id
values at runtime, the UUID does not need to be persisted in the database or
otherwise indexed to the ID it represents.

**Note:** The integer ID and namespace values are only _encoded_ in the UUID,
not _encrypted_, and the value can be recovered by a third party with effort.
This library is intended to support on-demand conversion between an integer and
a UUID, while mitigating basic "user enumeration attacks". Securely encrypting
a 64-bit integer in the 122 bits available in a UUID is currently outside the
scope of this library.

## Usage

#### Encode ID with Default Namespace (0) to UUID

```php
$id = \PhoneBurner\IntToUuid\IntegerId::make(12);
$uuid = \PhoneBurner\IntToUuid\IntToUuid::encode($id);
echo $uuid->toString(); // 14228ed0-822c-8d5d-b9c3-30d2a75c0e10
```

#### Encode ID with Namespace to UUID

```php
$id = \PhoneBurner\IntToUuid\IntegerId::make(42, 12);
$uuid = \PhoneBurner\IntToUuid\IntToUuid::encode($id);
echo $uuid->toString(); // 97ed98ee-0994-8f79-b993-bcb7a2905968
```

#### Decode UUID to ID and Namespace Integers

```php
$uuid = \Ramsey\Uuid\Uuid::fromString('97ed98ee-0994-8f79-b993-bcb7a2905968');
$id = \PhoneBurner\IntToUuid\IntToUuid::decode($uuid);
echo $id->value; // 42
echo $id->namespace; // 12
```

### Conversion Algorithm

Encoding an integer uses a deterministic seed based on the xxHash (`xxh3`) hash
of the concatenated binary strings packed from the id and namespace values. The
first 32-bits of the hash are used as the contiguous `time_hi_and_version`,
`clock_seq_hi_and_reserved`, and `clock_seq_low` fields. To comply with the RFC
4122 Version 8, the seed is multiplexed with the required Version and Variant
bits, leaving 26 bits of deterministic "pseudo-randomness". The encoded id is
the id integer packed as a 64-bit binary string XOR the xxHash hash of the
namespace and seed. The encoded namespace is the namespace integer packed as a
32-bit binary string XOR the xxHash hash of the seed. The resulting octets are
arranged into a valid UUID and a new `UuidInterface` (from the `ramsey/uuid`
library) is returned.

Decoding is the reverse of the encoding process: the UUID octets are split into
the encoded id, encoded namespace, and seed binary strings, XOR is applied to
the encoded values and corresponding hashes, and a "checksum" seed is produced
from the decoded binary strings, which are then unpacked into integer values.
If the seed value from the UUID does not match the checksum seed, then UUID does
not encode valid information, and an exception is thrown. An exception is also
thrown if the UUID passed into the decode function is not a valid Version 8
UUID.

#### RFC 4122 UUID Field Names and Bit Layout

```
 0                   1                   2                   3
 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|                           time_low                            |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|           time_mid            |      time_hi_and_version      |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|clk_seq_hi_res |  clk_seq_low  |          node (0-1)           |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|                          node (2-5)                           |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
```

#### Encoded Integer ID Field and Bit Layout

```
 0                   1                   2                   3
 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|                           namespace                           |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|           id (0-1)            |          seed (0-1)           |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|          seed (2-3)           |           id (2-3)            |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
|                           id (4-7)                            |
+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
```

### Why RFC 4122 Version 8?

The other UUID versions defined by RFC 4122 have distinct generation algorithms
and properties. Versions 1, 2, 6, and 7 are based on the current timestamp.
Version 3 (Name-Based MD5) and Version 5 (Name-Based SHA1) are deterministic
for a string "name" and "namespace" values, but are unidirectional,
because they are based on hash functions. Version 4 (Random) comes the closest
to fulfilling our needs -- 122 of the 128 bits are randomly/pseudo-randomly
generated. The same algorithm used here _could_ be used to generate encoded
UUIDs that _look_ like Version 4 UUIDs, but they would not be technically
compatible with the RFC definition, or have the expected universal uniqueness
property.

The proposed Version 8 defines a new, RFC-compatible format for experimental or
vendor-defined UUIDs. The definition allows for both implementation-specific
uniqueness and for the embedding of arbitrary information, both of which are key
to this particular use case. While Version 8 is currently in the IETF review
process, it is expected to be accepted without significant changes.
