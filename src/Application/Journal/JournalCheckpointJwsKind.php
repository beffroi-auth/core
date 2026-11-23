<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\KeyBoundArtifactInterface;
use DateInterval;

/**
 * A retiring key stays published until the head of the journal has been signed again under its successor.
 */
final readonly class JournalCheckpointJwsKind implements KeyBoundArtifactInterface
{
    public function __construct(
        private DateInterval $signedAgainWithin,
    ) {
    }

    public function keyUse(): KeyUse
    {
        return KeyUse::Signature;
    }

    public function longestLifetime(Tenant $tenant): DateInterval
    {
        return $this->signedAgainWithin;
    }
}
