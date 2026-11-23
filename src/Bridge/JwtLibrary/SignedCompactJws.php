<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Encoding\JoseExtension;
use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Domain\Key\VerifiedJws;
use Beffroi\Core\Exception\InvalidJwsException;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use function is_string;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Throwable;

/**
 * RFC 7515 §4.1.1, §4.1.11, RFC 8725 §3.1: a compact JWS of one signature, under an algorithm the provider signs with
 * and a key it names by "kid" alone; "jwk", "jku" and "x5c" are ignored.
 */
final readonly class SignedCompactJws
{
    /**
     * @param array<string, mixed> $protectedHeader
     */
    private function __construct(
        public JWS $jws,
        public array $protectedHeader,
        public string $algorithm,
        public string $kid,
    ) {
    }

    /**
     * @throws InvalidJwsException
     */
    public static function read(string $compactJws, JoseSignatureAlgorithms $algorithms): self
    {
        try {
            $jws = new CompactSerializer()->unserialize($compactJws);
        } catch (Throwable $exception) {
            throw new InvalidJwsException('The token is not a compact JWS.', 0, $exception);
        }
        if ($jws->countSignatures() !== 1) {
            throw new InvalidJwsException('The token carries an unexpected number of signatures.');
        }
        $header = $jws->getSignature(0)->getProtectedHeader();
        if (JoseExtension::isNamedIn($header)) {
            throw new InvalidJwsException('The token uses an extension of JOSE this provider does not understand (RFC 7515 section 4.1.11).');
        }
        $alg = $header['alg'] ?? null;
        if (! is_string($alg)) {
            throw new InvalidJwsException('The token does not name its algorithm.');
        }
        try {
            $algorithms->named($alg);
        } catch (UnsupportedAlgorithmException $exception) {
            throw new InvalidJwsException('The token uses an algorithm this provider does not sign with.', 0, $exception);
        }
        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new InvalidJwsException('The token does not name its key.');
        }

        return new self($jws, $header, $alg, $kid);
    }

    /**
     * @throws InvalidJwsException
     */
    public function verifiedWith(JWSVerifier $verifier, TenantKey $key): VerifiedJws
    {
        try {
            $verified = $verifier->verifyWithKey($this->jws, new JWK($key->publicJwk), 0);
        } catch (Throwable $exception) {
            throw new InvalidJwsException('The signature of the token cannot be checked.', 0, $exception);
        }
        if (! $verified) {
            throw new InvalidJwsException('The signature does not verify.');
        }
        $payload = $this->jws->getPayload();
        if ($payload === null) {
            throw new InvalidJwsException('The token has a detached payload.');
        }

        return new VerifiedJws($key, $this->protectedHeader, $payload);
    }
}
