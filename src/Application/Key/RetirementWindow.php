<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateTimeImmutable;
use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\KeyBoundArtifactInterface;

final readonly class RetirementWindow
{
    /**
     * @param iterable<KeyBoundArtifactInterface> $artifacts
     */
    public function __construct(
        private iterable $artifacts,
    ) {
    }

    public function dueAt(Tenant $tenant, KeyUse $use, DateTimeImmutable $retiringAt): DateTimeImmutable
    {
        $due = $retiringAt;
        foreach ($this->artifacts as $kind) {
            if ($kind->keyUse() !== $use) {
                continue;
            }
            $lifetime = $kind->longestLifetime($tenant);
            if ($lifetime === null) {
                continue;
            }
            $until = $retiringAt->add($lifetime);
            if ($until > $due) {
                $due = $until;
            }
        }

        return $due;
    }
}
