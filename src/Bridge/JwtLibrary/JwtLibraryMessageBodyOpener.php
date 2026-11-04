<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJweException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Port\MessageBodyOpenerInterface;
use Beffroi\Core\Port\MessageKeyProviderInterface;
use function is_string;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use function sprintf;
use Throwable;

/**
 * The key is the one the protected header names among the keys the tenant seals to, never guessed nor tried.
 */
final readonly class JwtLibraryMessageBodyOpener implements MessageBodyOpenerInterface
{
    private JWEDecrypter $decrypter;

    private CompactSerializer $serializer;

    public function __construct(
        private MessageKeyProviderInterface $keys,
        JoseMessageSealingAlgorithms $algorithms,
    ) {
        $this->decrypter = new JWEDecrypter($algorithms->manager());
        $this->serializer = new CompactSerializer();
    }

    public function open(Tenant $tenant, string $sealed): string
    {
        try {
            $jwe = $this->serializer->unserialize($sealed);
        } catch (Throwable $exception) {
            throw new InvalidJweException('The body of the message is not a compact JWE.', 0, $exception);
        }
        $header = $jwe->getSharedProtectedHeader();
        $scope = $header[SealedMessageBody::TENANT_HEADER] ?? null;
        if (! is_string($scope) || $scope !== $tenant->slug) {
            throw new InvalidJweException(sprintf(
                'The body of the message was sealed for another scope than tenant "%s"; it is not opened here.',
                $tenant->slug,
            ));
        }
        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new InvalidJweException('The body of the message does not name the key it was sealed to (RFC 7516 section 4.1.6).');
        }
        try {
            $unsealed = $this->keys->messageKey($tenant, $kid);
        } catch (KeyNotFoundException $exception) {
            throw new InvalidJweException($exception->getMessage(), 0, $exception);
        }
        $algorithm = $unsealed->key->algorithm;
        if (! $algorithm instanceof JoseMessageSealingAlgorithmInterface) {
            throw new InvalidJweException(sprintf('The key "%s" seals no message body of this provider.', $kid));
        }
        if (($header['alg'] ?? null) !== $algorithm->keyEncryption()->name() || ($header['enc'] ?? null) !== $algorithm->contentEncryption()->name()) {
            throw new InvalidJweException(sprintf(
                'The body of the message was sealed under another algorithm than the key "%s" is held for.',
                $kid,
            ));
        }
        // Under the JWE's "alg": the library refuses any other, and the stored pair carries the tenant's name.
        $jwk = new JWK([
            ...$unsealed->privateJwk,
            'alg' => $algorithm->keyEncryption()->name(),
        ]);
        if (! $this->decrypter->decryptUsingKey($jwe, $jwk, 0)) {
            throw new InvalidJweException(sprintf('The body of the message does not open with the key "%s" it names.', $kid));
        }

        return $jwe->getPayload() ?? throw new InvalidJweException('The body of the message carries no payload.');
    }
}
