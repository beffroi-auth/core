<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtFramework;

use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Key\VerifiedJws;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidJwsException;
use Heidi\Core\Port\JwsVerifierInterface;
use Heidi\Core\Port\SigningKeyProviderInterface;
use function is_string;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Throwable;

/**
 * Verifies what the tenant signed: the algorithm must be one the provider signs with (never "none", never
 * HMAC), the "kid" must name a published key of the tenant, and the signature must hold under that key.
 * The key is chosen by "kid" alone; the "jwk", "jku" and "x5c" headers are ignored (RFC 8725 §3.10).
 */
final readonly class JwtFrameworkJwsVerifier implements JwsVerifierInterface
{
    private JWSVerifier $verifier;

    private CompactSerializer $serializer;

    public function __construct(
        private SigningKeyProviderInterface $keys,
    ) {
        $this->verifier = new JWSVerifier(new AlgorithmManager([new RS256()]));
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
        $header = $jws->getSignature(0)
            ->getProtectedHeader();
        $alg = $header['alg'] ?? null;
        if (! is_string($alg) || SignatureAlgorithm::tryFrom($alg) === null) {
            throw new InvalidJwsException('The token uses an algorithm this provider does not sign with.');
        }
        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new InvalidJwsException('The token does not name its key.');
        }
        $key = $this->keys->publicKeys($tenant)
            ->find($kid);
        if ($key === null || $key->algorithm->value !== $alg) {
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
