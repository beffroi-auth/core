<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * One JWE key management algorithm the provider is encrypted to (RFC 7518 §4): the "alg" that wraps, or
 * agrees on, the content encryption key of a JWE a client sends. FAPI 2.0 Message Signing §5.4 asks for
 * an algorithm that gives forward secrecy or an RSA-OAEP one, which is why the two wired here are
 * "ECDH-ES+A256KW" over P-256 (§4.6) and "RSA-OAEP-256" (§4.3); RSA1_5 is refused everywhere
 * (RFC 8725 §3.4) and "dir" would mean a shared secret, which a provider published key is not.
 */
interface KeyEncryptionAlgorithmInterface extends KeyAlgorithmInterface
{
}
