<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\KeyUse;
use Heidi\Core\Domain\Key\VerifiedJws;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidJwsException;
use Heidi\Core\Exception\UnsupportedAlgorithmException;
use Heidi\Core\Port\JwsVerifierInterface;
use Heidi\Core\Port\PublicKeySetProviderInterface;
use function is_string;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Throwable;

/**
 * The key is chosen by "kid" alone among the published keys of the tenant that sign; "jwk", "jku" and
 * "x5c" are ignored (RFC 8725 §3.10), and a key the tenant publishes to be encrypted to is not among the
 * candidates (RFC 7517 §4.2). The "alg" of the header has to be one the provider signs with and the one
 * the key names (RFC 7515 §4.1.1, RFC 8725 §3.1).
 */
final readonly class JwtLibraryJwsVerifier implements JwsVerifierInterface
{
    private JWSVerifier $verifier;

    private CompactSerializer $serializer;

    public function __construct(
        private PublicKeySetProviderInterface $keys,
        private JoseSignatureAlgorithms $algorithms,
    ) {
        $this->verifier = new JWSVerifier($algorithms->manager());
        $this->serializer = new CompactSerializer();
    }

    public function verify(Tenant $tenant, string $compactJws): VerifiedJws
    {
        try {
            $jws = $this->serializer->unserialize($compactJws);
        } catch (Throwable $exception) {
            throw new InvalidJwsException('The token is not a compact JWS.', 0, $exception);
        }
        if ($jws->countSignatures() !== 1) {
            throw new InvalidJwsException('The token carries an unexpected number of signatures.');
        }
        $header = $jws->getSignature(0)->getProtectedHeader();
        $alg = $header['alg'] ?? null;
        if (! is_string($alg)) {
            throw new InvalidJwsException('The token does not name its algorithm.');
        }
        try {
            $this->algorithms->named($alg);
        } catch (UnsupportedAlgorithmException $exception) {
            throw new InvalidJwsException('The token uses an algorithm this provider does not sign with.', 0, $exception);
        }
        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new InvalidJwsException('The token does not name its key.');
        }
        $key = $this->keys->publicKeys($tenant)->forUse(KeyUse::Signature)->find($kid);
        if ($key === null || $key->algorithm->name() !== $alg) {
            throw new InvalidJwsException('The token names a key this tenant does not publish.');
        }
        if (! $this->verifier->verifyWithKey($jws, new JWK($key->publicJwk), 0)) {
            throw new InvalidJwsException('The signature does not verify.');
        }
        $payload = $jws->getPayload();
        if ($payload === null) {
            throw new InvalidJwsException('The token has a detached payload.');
        }

        return new VerifiedJws($key, $header, $payload);
    }
}
