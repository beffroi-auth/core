<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\GroupMembership;

/**
 * Where the memberships of a person are written (decision 0002), beside GroupsOfAPersonInterface, which reads
 * them. What becomes of the memberships of a source when another one writes is the rule of the use case that
 * writes, never the store's: the store writes one and takes one back.
 */
interface GroupMembershipRepositoryInterface
{
    public function save(GroupMembership $membership): void;

    /**
     * A membership taken back: the row goes, the group and the person stay.
     */
    public function remove(GroupMembership $membership): void;
}
