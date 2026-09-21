<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Domain\Key\SignatureAlgorithmInterface;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\JwsSignerInterface;
use Heidi\Core\Port\SigningKeyProviderInterface;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

final readonly class JwtLibraryJwsSigner implements JwsSignerInterface
{
    private JWSBuilder $builder;

    private CompactSerializer $serializer;

    public function __construct(
        private SigningKeyProviderInterface $keys,
        JoseSignatureAlgorithms $algorithms,
    ) {
        $this->builder = new JWSBuilder($algorithms->manager());
        $this->serializer = new CompactSerializer();
    }

    public function sign(Tenant $tenant, SignatureAlgorithmInterface $algorithm, string $payload, array $protectedHeader = []): string
    {
        $unsealed = $this->keys->activeKey($tenant, $algorithm);
        $jws = $this->builder->create()
            ->withPayload($payload)
            ->addSignature(new JWK($unsealed->privateJwk), [
                ...$protectedHeader,
                'alg' => $algorithm->name(),
                'kid' => $unsealed->key->kid,
            ])
            ->build();

        return $this->serializer->serialize($jws, 0);
    }
}
