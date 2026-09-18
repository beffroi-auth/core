<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Group;

use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
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
