<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use function array_map;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use function implode;
use Beffroi\Core\Port\KeyAlgorithmsInterface;

/**
 * Every algorithm a key pair may be generated for, the registries of one use read as one: what the key
 * commands resolve a "--algorithm" against and what reads a stored key back. It holds no list of its own,
 * so a wiring is written once and the views of it cannot disagree (issue #98).
 */
final readonly class JoseKeyAlgorithms implements KeyAlgorithmsInterface
{
    public function __construct(
        private JoseSignatureAlgorithms $signature,
        private JoseKeyEncryptionAlgorithms $keyEncryption,
        private JoseApplicationServerAlgorithms $applicationServer,
        private JoseMessageSealingAlgorithms $messageSealing,
    ) {
    }

    /**
     * @return list<JoseKeyAlgorithmInterface>
     */
    public function all(): array
    {
        return [...$this->signature->all(), ...$this->keyEncryption->all(), ...$this->applicationServer->all(), ...$this->messageSealing->all()];
    }

    public function named(string $name): JoseKeyAlgorithmInterface
    {
        $refusals = [];
        foreach ([$this->signature, $this->keyEncryption, $this->applicationServer, $this->messageSealing] as $registry) {
            try {
                return $registry->named($name);
            } catch (UnsupportedAlgorithmException $refusal) {
                $refusals[] = $refusal;
            }
        }
        // what a registry does not find, it words for its own use: a name nothing carries is refused
        // with what each of them had to say about it, and the first refusal is the trail
        throw new UnsupportedAlgorithmException(
            implode(' ', array_map(static fn (UnsupportedAlgorithmException $refusal): string => $refusal->getMessage(), $refusals)),
            0,
            $refusals[0],
        );
    }
}
