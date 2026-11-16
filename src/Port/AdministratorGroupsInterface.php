<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The groups whose members administer a tenant (decision 0038): the role of an administrator is read from the groups
 * of the person (decision 0002), local or held by a directory, and never from a list of people kept beside them.
 */
interface AdministratorGroupsInterface
{
    /**
     * @return list<string> the names of those groups, none for a tenant nobody administers
     */
    public function groupsAdministering(Tenant $tenant): array;
}
