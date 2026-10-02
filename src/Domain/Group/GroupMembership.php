<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class GroupMembership
{
    /**
     * Restated from the group (decision 0002).
     */
    public readonly Tenant $tenant;

    public function __construct(
        public private(set) Uuid $id,
        public readonly Group $group,
        public readonly User $user,
        public readonly GroupSource $source,
        public readonly DateTimeImmutable $addedAt,
    ) {
        if (! $user->belongsTo($group->tenant)) {
            throw new InvalidArgumentException('A user can only belong to a group of their own tenant.');
        }
        $this->tenant = $group->tenant;
    }
}
