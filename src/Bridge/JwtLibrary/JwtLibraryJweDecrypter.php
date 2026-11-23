<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Encoding\JoseExtension;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJweException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\DecryptionKeyProviderInterface;
use Beffroi\Core\Port\JweDecrypterInterface;
use function is_string;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Throwable;

/**
 * RFC 7516 §4.1.13, §7.1, RFC 8725 §3.1
 *
 * The key is the one the "kid" of the header names, never one found by trial among the published keys.
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
        if (JoseExtension::isNamedIn($header)) {
            throw new InvalidJweException('The token uses an extension of JOSE this provider does not understand (RFC 7516 section 4.1.13).');
        }
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
        $privateKey = new JWK($unsealed->privateJwk);
        try {
            $opened = $this->decrypter->decryptUsingKey($jwe, $privateKey, 0);
        } catch (Throwable $exception) {
            throw new InvalidJweException('The token cannot be opened as a JWE (RFC 7516 section 5.2).', 0, $exception);
        }
        if (! $opened) {
            throw new InvalidJweException('The payload does not open with the key the token names.');
        }
        $payload = $jwe->getPayload();
        if ($payload === null) {
            throw new InvalidJweException('The token carries no payload.');
        }

        return $payload;
    }
}
