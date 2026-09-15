<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtFramework;

use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\JwsSignerInterface;
use Heidi\Core\Port\SigningKeyProviderInterface;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

final readonly class JwtFrameworkJwsSigner implements JwsSignerInterface
{
    private JWSBuilder $builder;

    private CompactSerializer $serializer;

    public function __construct(
        private SigningKeyProviderInterface $keys,
    ) {
        $this->builder = new JWSBuilder(new AlgorithmManager([new RS256()]));
        $this->serializer = new CompactSerializer();
    }

    public function sign(Tenant $tenant, SignatureAlgorithm $algorithm, string $payload, array $protectedHeader = []): string
    {
        $unsealed = $this->keys->activeKey($tenant, $algorithm);
        $jws = $this->builder->create()
            ->withPayload($payload)
            ->addSignature(new JWK($unsealed->privateJwk), [
                ...$protectedHeader,
                'alg' => $algorithm->value,
                'kid' => $unsealed->key->kid,
            ])
            ->build();

        return $this->serializer->serialize($jws, 0);
    }
}
