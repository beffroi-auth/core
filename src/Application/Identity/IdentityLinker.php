<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\LinkedIdentityRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;

final readonly class IdentityLinker
{
    public function __construct(
        private LinkedIdentityRepositoryInterface $identities,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array<string, mixed> $claims
     *
     * @throws InvalidArgumentException
     * @throws IdentityAlreadyLinkedException
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
