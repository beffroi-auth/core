<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Key\VerifiedJws;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJwsException;
use Beffroi\Core\Port\HeldKeyJwsVerifierInterface;
use Beffroi\Core\Port\TenantKeyRepositoryInterface;
use Jose\Component\Signature\JWSVerifier;

/**
 * RFC 7515 §4.1.1, RFC 8725 §3.1: the key is the one of the tenant that "kid" names, whatever its state.
 */
final readonly class JwtLibraryHeldKeyJwsVerifier implements HeldKeyJwsVerifierInterface
{
    private JWSVerifier $verifier;

    public function __construct(
        private TenantKeyRepositoryInterface $keys,
        private JoseSignatureAlgorithms $algorithms,
    ) {
        $this->verifier = new JWSVerifier($algorithms->manager());
    }

    public function verify(Tenant $tenant, string $compactJws): VerifiedJws
    {
        $signed = SignedCompactJws::read($compactJws, $this->algorithms);
        $key = $this->keys->find($tenant, $signed->kid);
        if ($key === null || $key->algorithm->keyUse() !== KeyUse::Signature || $key->algorithm->name() !== $signed->algorithm) {
            throw new InvalidJwsException('The token names a key this tenant never signed with.');
        }
        return $signed->verifiedWith($this->verifier, $key);
    }
}
