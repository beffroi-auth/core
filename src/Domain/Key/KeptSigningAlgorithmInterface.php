<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * One algorithm a key pair the tenant signs with is generated for, a key it keeps out of its JWK Set (RFC 7517 §5)
 * because what it signs is verified under a public half published elsewhere: the SAML signing key of a tenant is the
 * one there is, whose certificate the metadata of its identity provider carry and no relying party of OpenID Connect
 * has a use for (decision 0020).
 *
 * A key of the tenant in every other way: drawn by the generator, sealed by the KMS, staged, active, retiring and
 * retired on the same four steps (architecture §3.3), and opened by the KMS once per process when it signs, as the
 * keys of the JWK Set are, so that the KMS is never asked at every message it signs (decision 0018). Its use is "sig".
 */
interface KeptSigningAlgorithmInterface extends KeyAlgorithmInterface
{
}
