<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Identity;

use Heidi\Core\Domain\Identity\LinkedIdentity;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Port\LinkedIdentityRepositoryInterface;
use Heidi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;

/**
 * Says that a user of the tenant is a given subject at a given upstream provider (decision 0002), from a
 * console, a fixture or an import. Synchronous writes, no session, no event, as the provisioning of a user is.
 *
 * It is the one way a row of that table comes to exist outside a flow that receives one: whoever presents that
 * subject at that provider is that user, and nothing infers such a row. A grant that reads an assertion of a
 * trusted issuer (RFC 7523 §3) resolves its subject through one and creates none, so an identity that was never
 * written is an assertion nobody is.
 *
 * Linking the same identity again is what an import does on its second run: the claims are refreshed and the
 * row keeps its user. Linking it to somebody else is refused, because a subject names one user of a tenant.
 */
final readonly class IdentityLinker
{
    public function __construct(
        private LinkedIdentityRepositoryInterface $identities,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<string, mixed> $claims the last snapshot the provider gave of that subject, empty when it gave none
     *
     * @throws InvalidArgumentException when the provider or the subject is empty
     * @throws IdentityAlreadyLinkedException when another user of the tenant is already that identity
     */
    public function link(User $user, string $provider, string $subject, array $claims = []): LinkedIdentity
    {
        $now = $this->clock->now();
        $linked = $this->identities->find($user->tenant, $provider, $subject);
        if ($linked === null) {
            $linked = new LinkedIdentity($this->uuids->timeOrdered(), $user, $provider, $subject, $claims, $now, $now);
            $this->identities->save($linked);

            return $linked;
        }
        if (! $linked->user->id->equals($user->id)) {
            throw IdentityAlreadyLinkedException::toAnotherUser($provider, $subject, $linked->user->username);
        }
        $linked->refresh($claims, $now);
        $this->identities->save($linked);

        return $linked;
    }
}
