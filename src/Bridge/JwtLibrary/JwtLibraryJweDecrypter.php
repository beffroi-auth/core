<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidJweException;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Exception\UnsupportedAlgorithmException;
use Heidi\Core\Port\DecryptionKeyProviderInterface;
use Heidi\Core\Port\JweDecrypterInterface;
use function is_string;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Throwable;

/**
 * Opens a JWE a client encrypted to the provider (RFC 7516 §7.1). The key is chosen by the "kid" of the
 * header alone, among the encryption keys the tenant publishes: a set of keys is not a set of keys to
 * try, and an object that names none is refused rather than opened by trial. The "alg" and the "enc" are
 * the ones the provider is wired with (RFC 8725 §3.1), and the key the header names has to be published
 * for encryption and still published, which is what makes a retirement a refusal and not a decryption.
 */
final readonly class JwtLibraryJweDecrypter implements JweDecrypterInterface
{
    private JWEDecrypter $decrypter;

    private CompactSerializer $serializer;

    public function __construct(
        private DecryptionKeyProviderInterface $keys,
        private JoseEncryptionAlgorithms $algorithms,
    ) {
        $this->decrypter = new JWEDecrypter($algorithms->manager());
        $this->serializer = new CompactSerializer();
    }

    public function decrypt(Tenant $tenant, string $compactJwe): string
    {
        try {
            $jwe = $this->serializer->unserialize($compactJwe);
        } catch (Throwable $exception) {
            throw new InvalidJweException('The token is not a compact JWE.', 0, $exception);
        }
        $header = $jwe->getSharedProtectedHeader();
        $alg = $header['alg'] ?? null;
        if (! is_string($alg)) {
            throw new InvalidJweException('The token does not name its key management algorithm.');
        }
        try {
            $this->algorithms->keyEncryption->named($alg);
        } catch (UnsupportedAlgorithmException $exception) {
            throw new InvalidJweException('The token is encrypted to with an algorithm this provider is not wired with.', 0, $exception);
        }
        $enc = $header['enc'] ?? null;
        if (! is_string($enc)) {
            throw new InvalidJweException('The token does not name how its payload is encrypted.');
        }
        try {
            $this->algorithms->contentEncryption->named($enc);
        } catch (UnsupportedAlgorithmException $exception) {
            throw new InvalidJweException('The token encrypts its payload with a method this provider is not wired with.', 0, $exception);
        }
        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new InvalidJweException('The token does not name the key it was encrypted to (RFC 7516 section 4.1.6).');
        }
        try {
            $unsealed = $this->keys->decryptionKey($tenant, $kid);
        } catch (KeyNotFoundException $exception) {
            throw new InvalidJweException($exception->getMessage(), 0, $exception);
        }
        if ($unsealed->key->algorithm->name() !== $alg) {
            throw new InvalidJweException('The token names a key this tenant publishes for another algorithm.');
        }
        if (! $this->decrypter->decryptUsingKey($jwe, new JWK($unsealed->privateJwk), 0)) {
            throw new InvalidJweException('The payload does not open with the key the token names.');
        }
        $payload = $jwe->getPayload();
        if ($payload === null) {
            throw new InvalidJweException('The token carries no payload.');
        }

        return $payload;
    }
}
