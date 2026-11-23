<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Key\VerifiedJws;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJwsException;
use Beffroi\Core\Port\JwsVerifierInterface;
use Beffroi\Core\Port\PublicKeySetProviderInterface;
use Jose\Component\Signature\JWSVerifier;

/**
 * RFC 7515 §4.1.1, §4.1.11, RFC 7517 §4.2, RFC 8725 §3.1, §3.10
 */
final readonly class JwtLibraryJwsVerifier implements JwsVerifierInterface
{
    private JWSVerifier $verifier;

    public function __construct(
        private PublicKeySetProviderInterface $keys,
        private JoseSignatureAlgorithms $algorithms,
    ) {
        $this->verifier = new JWSVerifier($algorithms->manager());
    }

    public function verify(Tenant $tenant, string $compactJws): VerifiedJws
    {
        $signed = SignedCompactJws::read($compactJws, $this->algorithms);
        $key = $this->keys->publicKeys($tenant)->forUse(KeyUse::Signature)->find($signed->kid);
        if ($key === null || $key->algorithm->name() !== $signed->algorithm) {
            throw new InvalidJwsException('The token names a key this tenant does not publish.');
        }
        return $signed->verifiedWith($this->verifier, $key);
    }
}
