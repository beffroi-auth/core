<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * What a key of a tenant is published for (RFC 7517 §4.2): the "use" member of the JWK. The registry of
 * that member holds these two values and no other, which is why this is an enumeration and not a class
 * per use: a signature and an encryption are what JOSE does, and a deployment adds neither.
 *
 * A key does one of the two and never both (RFC 7517 §4.2, NIST SP 800-57 Part 1 Rev. 5 §5.2): a key that
 * signs is never asked to open a JWE, a key that opens one never signs, and the provider asserts it
 * rather than assuming it.
 */
enum KeyUse: string
{
    case Signature = 'sig';

    case Encryption = 'enc';
}
