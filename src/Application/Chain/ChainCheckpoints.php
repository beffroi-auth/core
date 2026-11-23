<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Chain;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\Checkpoint;
use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenChainException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\InvalidJwsException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\CheckpointCopiesInterface;
use Beffroi\Core\Port\HeldKeyJwsVerifierInterface;
use Beffroi\Core\Port\JwsSignerInterface;
use Beffroi\Core\Port\SignatureAlgorithmsInterface;
use Beffroi\Core\Port\SignedCheckpointsInterface;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use JsonException;
use function sprintf;

/**
 * RFC 7515 §7.1, RFC 8725 §3.11: signed under the active key of the default algorithm, with a type no client takes for
 * a token, and read back under any key the tenant held, so that a key retired at once leaves a signature that verifies.
 */
final readonly class ChainCheckpoints
{
    public const string TYPE = 'chain-checkpoint+jwt';

    public function __construct(
        private SignedCheckpointsInterface $kept,
        private CheckpointCopiesInterface $copies,
        private JwsSignerInterface $signer,
        private HeldKeyJwsVerifierInterface $verifier,
        private SignatureAlgorithmsInterface $algorithms,
    ) {
    }

    /**
     * @throws BrokenChainException when the one kept does not verify
     */
    public function last(Tenant $tenant, ChainInterface $chain): ?Checkpoint
    {
        $signed = $this->kept->last($tenant, $chain);

        return $signed === null ? null : $this->read($tenant, $chain, $signed);
    }

    /**
     * A signature kept anywhere, a copy that left the instance included.
     *
     * @throws BrokenChainException when it does not verify, or signs another chain
     */
    public function read(Tenant $tenant, ChainInterface $chain, string $signed): Checkpoint
    {
        try {
            $verified = $this->verifier->verify($tenant, $signed);
        } catch (InvalidJwsException $exception) {
            throw new BrokenChainException(
                $chain->name(),
                null,
                sprintf('The signature of the chain "%s" does not verify: %s', $chain->name(), $exception->getMessage()),
                $exception,
            );
        }
        if (($verified->protectedHeader['typ'] ?? null) !== self::TYPE) {
            throw new BrokenChainException(
                $chain->name(),
                null,
                sprintf('The signature of the chain "%s" is not of the type "%s".', $chain->name(), self::TYPE),
            );
        }
        try {
            $checkpoint = Checkpoint::fromDocument(Document::of(json_decode($verified->payload, true, 8, JSON_THROW_ON_ERROR), 'checkpoint of a chain'));
        } catch (JsonException|InvalidArgumentException $exception) {
            throw new BrokenChainException(
                $chain->name(),
                null,
                sprintf('The signature of the chain "%s" signs no checkpoint of a chain.', $chain->name()),
                $exception,
            );
        }
        if (! $checkpoint->tenant->equals($tenant->id) || $checkpoint->chain !== $chain->name()) {
            throw new BrokenChainException(
                $chain->name(),
                null,
                sprintf('The signature of the chain "%s" signs another chain or the chain of another tenant.', $chain->name()),
            );
        }

        return $checkpoint;
    }

    /**
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException when it cannot be kept
     */
    public function sign(Tenant $tenant, ChainInterface $chain, Checkpoint $checkpoint): void
    {
        $signed = $this->signer->sign(
            $tenant,
            $this->algorithms->defaultAlgorithm(),
            json_encode($checkpoint->toDocument(), JSON_THROW_ON_ERROR),
            ['typ' => self::TYPE],
        );
        $this->kept->keep($tenant, $chain, $signed);
        $this->copies->copy($tenant, $checkpoint, $signed);
    }
}
