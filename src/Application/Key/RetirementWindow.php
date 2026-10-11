<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use DateTimeImmutable;
use Beffroi\Core\Domain\Key\KeyUse;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\KeyBoundArtifactInterface;

/**
 * How long a key of a use holds the JWK Set open once another key has taken over (architecture §3.3): the
 * longest lifetime among the kinds of artifact bound to a key of that use, the JWS the tenant issues for a
 * key that signs, what a client may still be encrypting to it for a key it publishes to be encrypted to.
 * None of them means at once.
 *
 * It is one object because two steps of the life of a key need the same reading and may not read it
 * differently: the take-over dates the retirement of the key it takes over from with it, and the
 * succession compares the age a deployment gives a key with it before staging a successor. The lifetimes
 * themselves are declared where they are fixed, each kind naming the use it holds back; nothing here
 * enumerates the kinds and no lifetime is written twice.
 */
final readonly class RetirementWindow
{
    /**
     * @param iterable<KeyBoundArtifactInterface> $artifacts the kinds of artifact the tenant binds to a key
     */
    public function __construct(
        private iterable $artifacts,
    ) {
    }

    /**
     * When a key of that use, retiring at that moment, has nothing alive bound to it any more.
     */
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
