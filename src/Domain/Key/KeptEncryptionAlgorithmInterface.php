<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * One algorithm a key pair the tenant is encrypted to is generated for, a key it keeps out of its JWK Set (RFC 7517 §5)
 * because what a peer encrypts to it is encrypted for a public half published elsewhere: the key an upstream SAML
 * identity provider encrypts an assertion for, whose certificate the metadata of the service provider of the tenant
 * give to that provider alone, is the one there is (decision 0020).
 *
 * A key of the tenant in every other way: drawn by the generator, sealed by the KMS, staged, active, retiring and
 * retired on the same four steps (architecture §3.3), and opened by the KMS once per process when it decrypts, as the
 * keys of the JWK Set are. Its use is "enc". It does not extend KeyEncryptionAlgorithmInterface, whose keys the tenant
 * publishes for a client to encrypt a JWE to: no client of OpenID Connect is handed it, and what opens a JWE never
 * answers it (DecryptionKeyProviderInterface).
 */
interface KeptEncryptionAlgorithmInterface extends KeyAlgorithmInterface
{
}
