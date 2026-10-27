<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\OpenedSigningKeyInterface;
use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\JwsSignerInterface;
use Beffroi\Core\Port\SigningKeyOpenerInterface;
use Beffroi\Core\Port\SigningKeyProviderInterface;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

final readonly class JwtLibraryJwsSigner implements JwsSignerInterface, SigningKeyOpenerInterface
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
        return $this->open($tenant, $algorithm)->sign($payload, $protectedHeader);
    }

    public function open(Tenant $tenant, SignatureAlgorithmInterface $algorithm): OpenedSigningKeyInterface
    {
        return new JwtLibraryOpenedSigningKey($this->builder, $this->serializer, $this->keys->activeKey($tenant, $algorithm));
    }
}
