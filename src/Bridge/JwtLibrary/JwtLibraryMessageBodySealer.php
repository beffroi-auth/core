<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\MessageBodySealerInterface;
use Beffroi\Core\Port\MessageKeyProviderInterface;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use function sprintf;
use Throwable;

final readonly class JwtLibraryMessageBodySealer implements MessageBodySealerInterface
{
    private JWEBuilder $builder;

    private CompactSerializer $serializer;

    public function __construct(
        private MessageKeyProviderInterface $keys,
        private JoseMessageSealingAlgorithms $algorithms,
    ) {
        $this->builder = new JWEBuilder($algorithms->manager());
        $this->serializer = new CompactSerializer();
    }

    public function seal(Tenant $tenant, string $body): string
    {
        $algorithm = $this->algorithms->defaultAlgorithm();
        $key = $this->keys->sealingKey($tenant, $algorithm);
        $alg = $algorithm->keyEncryption()->name();

        try {
            $jwe = $this->builder
                ->create()
                ->withPayload($body)
                ->withSharedProtectedHeader([
                    'alg' => $alg,
                    'enc' => $algorithm->contentEncryption()->name(),
                    'kid' => $key->kid,
                    SealedMessageBody::TENANT_HEADER => $tenant->slug,
                ])
                // Under the JWE's "alg", not the tenant's name for the pair: the library refuses any other.
                ->addRecipient(new JWK([
                    ...$key->publicJwk,
                    'alg' => $alg,
                ]))
                ->build();

            return $this->serializer->serialize($jwe, 0);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('The body of a message could not be sealed to the key "%s" of tenant "%s" under "%s".', $key->kid, $tenant->slug, $alg),
                0,
                $exception,
            );
        }
    }
}
