<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Symfony\Component\Uid\Uuid;

/**
 * Where the identifiers of the core come from. A use case is given its clock (PSR-20) rather than
 * reading the wall clock, and identity is the other ambient input: it is given too, so that a test can
 * fix what a row will be called, an import can bring the identifiers it already has, and a deployment
 * that needs its own draw can provide one.
 *
 * The values themselves stay UUIDs, as a clock hands back a DateTimeImmutable: what has to be inverted
 * is the source, not the value.
 */
interface UuidGeneratorInterface
{
    /**
     * Time-ordered (UUID version 7, RFC 9562 §5.7): what a row is keyed by, so that what is created
     * together is stored together and an index does not scatter.
     */
    public function timeOrdered(): Uuid;

    /**
     * Saying nothing of when it was drawn (UUID version 4, RFC 9562 §5.4): what a value a party gets to
     * see is made of, a "sub" above all, which must not date the account it names.
     */
    public function opaque(): Uuid;
}
