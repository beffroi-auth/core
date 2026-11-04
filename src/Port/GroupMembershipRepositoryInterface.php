<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Group\GroupMembership;

interface GroupMembershipRepositoryInterface
{
    public function save(GroupMembership $membership): void;

    public function remove(GroupMembership $membership): void;
}
