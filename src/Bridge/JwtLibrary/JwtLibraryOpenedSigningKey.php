<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\OpenedSigningKeyInterface;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

/**
 * Signs under the private half it holds, which never leaves the bridge.
 */
final readonly class JwtLibraryOpenedSigningKey implements OpenedSigningKeyInterface
{
    public function __construct(
        private JWSBuilder $builder,
        private CompactSerializer $serializer,
        private UnsealedKey $key,
    ) {
    }

    public function sign(string $payload, array $protectedHeader = []): string
    {
        $jws = $this->builder->create()->withPayload($payload)->addSignature(new JWK($this->key->privateJwk), [
            ...$protectedHeader,
            'alg' => $this->key->key->algorithm->name(),
            'kid' => $this->key->key->kid,
        ])->build();

        return $this->serializer->serialize($jws, 0);
    }
}
