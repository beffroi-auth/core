<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Domain\Journal\Checkpoint;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenJournalException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\InvalidJwsException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\JwsSignerInterface;
use Beffroi\Core\Port\JwsVerifierInterface;
use Beffroi\Core\Port\SignatureAlgorithmsInterface;
use Beffroi\Core\Port\SignedCheckpointsInterface;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use JsonException;
use function sprintf;

/**
 * RFC 7515 §7.1, RFC 8725 §3.11: signed under the active key of the default algorithm of the tenant, with a type no
 * client takes for a token.
 */
final readonly class JournalCheckpoints
{
    public const string TYPE = 'journal-checkpoint+jwt';

    public function __construct(
        private SignedCheckpointsInterface $kept,
        private JwsSignerInterface $signer,
        private JwsVerifierInterface $verifier,
        private SignatureAlgorithmsInterface $algorithms,
    ) {
    }

    /**
     * @throws BrokenJournalException when the one kept does not verify
     */
    public function last(Tenant $tenant): ?Checkpoint
    {
        $signed = $this->kept->last($tenant);
        if ($signed === null) {
            return null;
        }
        try {
            $verified = $this->verifier->verify($tenant, $signed);
        } catch (InvalidJwsException $exception) {
            throw new BrokenJournalException(null, sprintf('The last signature of the journal does not verify: %s', $exception->getMessage()), $exception);
        }
        if (($verified->protectedHeader['typ'] ?? null) !== self::TYPE) {
            throw new BrokenJournalException(null, sprintf('The last signature of the journal is not of the type "%s".', self::TYPE));
        }
        try {
            $checkpoint = Checkpoint::fromDocument(Document::of(json_decode($verified->payload, true, 8, JSON_THROW_ON_ERROR), 'checkpoint of the journal'));
        } catch (JsonException|InvalidArgumentException $exception) {
            throw new BrokenJournalException(null, 'The last signature of the journal signs no checkpoint of a journal.', $exception);
        }
        if (! $checkpoint->tenant->equals($tenant->id)) {
            throw new BrokenJournalException(null, 'The last signature of the journal signs the checkpoint of another tenant.');
        }

        return $checkpoint;
    }

    /**
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException when it cannot be kept
     */
    public function sign(Tenant $tenant, Checkpoint $checkpoint): void
    {
        $this->kept->keep($tenant, $this->signer->sign(
            $tenant,
            $this->algorithms->defaultAlgorithm(),
            json_encode($checkpoint->toDocument(), JSON_THROW_ON_ERROR),
            ['typ' => self::TYPE],
        ));
    }
}
