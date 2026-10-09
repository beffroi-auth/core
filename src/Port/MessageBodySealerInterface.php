<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\RuntimeException;

/**
 * Seals what leaves the process to the message key of the tenant (decision 0020): what a broker, or the
 * table of a transport, keeps is worth nothing without the KMS, exactly as a column is at rest
 * (decision 0011).
 *
 * The scope of the sealing is the tenant and it is written in the authenticated data of the seal, so a
 * body cannot be opened under another scope than the one it was sealed under. What the caller hands over
 * is bytes: what those bytes are is the business of whoever writes messages.
 */
interface MessageBodySealerInterface
{
    /**
     * @param string $body what the transport would otherwise carry in the clear
     *
     * @return string a compact JWE, which is ASCII and goes on a broker and in a column as it is
     *
     * @throws KeyNotFoundException when the tenant has no active message key: nothing is written rather
     *                              than something written in the clear
     * @throws RuntimeException when the key the tenant holds cannot receive a JWE under the algorithm it
     *                          was generated for
     */
    public function seal(Tenant $tenant, string $body): string;
}
