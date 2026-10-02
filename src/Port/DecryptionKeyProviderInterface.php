<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * What opens a JWE a client encrypted to the provider: the published encryption key the JOSE header names,
 * opened by the KMS exactly as a signing key is. A port of its own, because opening a JWE and signing a
 * JWS are two capabilities and a caller does one of them.
 *
 * The key is named by its "kid" and not guessed: the tenant publishes several, and a set of keys is not a
 * set of keys to try. A key that has retired does not come back, which is what bounds the retirement
 * window of an encryption key.
 */
interface DecryptionKeyProviderInterface
{
    /**
     * @param string $kid the "kid" of the JOSE header of the JWE (RFC 7516 §4.1.6)
     *
     * @throws KeyNotFoundException when the tenant publishes no encryption key under that identifier, or
     *                              publishes a key that signs, or published one that has retired since
     * @throws KmsUnavailableException when the KMS does not answer to open the key, which is not open yet
     */
    public function decryptionKey(Tenant $tenant, string $kid): UnsealedKey;
}
