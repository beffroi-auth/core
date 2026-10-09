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

/**
 * Seals a message body to the key the tenant keeps for it (decision 0020), on web-token/jwt-library.
 *
 * **The KMS is asked nothing.** What seals is the public half, which the key carries in the clear on the
 * line, so a provider that raises several events while answering a browser pays no round trip for them and
 * holds no key material in the process that answers browsers. The ephemeral key of the sender makes every
 * body its own secret, which is what the key management algorithm of the pair gives.
 *
 * The protected header names the key, the two algorithms and the scope. The scope is in there and not only
 * in a header of the transport because the protected header is what the sealing authenticates: a body
 * moved to the queue of another tenant does not open there.
 */
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
        $key = $this->keys->activeMessageKey($tenant, $algorithm);
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
                // the pair is named by what the tenant keeps it for and the JWE by what JOSE calls what
                // seals it (MessageSeal), so the key is handed over under the name it is sealed under
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
