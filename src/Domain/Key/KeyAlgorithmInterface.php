<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * One algorithm a key pair of the tenant is generated and published for: what it is called, the kind of
 * key pair it takes and what that pair is used for. What the provider is wired with is what it generates,
 * what it publishes and what it announces: a new algorithm is a class that implements the interface of
 * its use and a line in the wiring, never a case added to an enumeration.
 */
interface KeyAlgorithmInterface extends NamedAlgorithmInterface
{
    /**
     * The "kty" of the key pairs it takes (RFC 7518 §6.1).
     *
     * @return non-empty-string
     */
    public function keyType(): string;

    /**
     * The "use" of the published JWK (RFC 7517 §4.2).
     */
    public function keyUse(): KeyUse;
}
