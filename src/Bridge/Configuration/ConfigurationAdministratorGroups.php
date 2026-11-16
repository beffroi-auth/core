<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Configuration;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AdministratorGroupsInterface;
use function in_array;
use function sprintf;
use function trim;

/**
 * The groups whose members administer the tenant a deployment declares (decision 0038), named by the deployment and
 * read once, when this service is built. A group is named as it is named in the tenant, exactly: a name that is empty,
 * or that begins or ends with whitespace, which no group carries, refuses that construction rather than make nobody
 * an administrator in silence. A name no group carries yet is kept, the group being data the tenant may create later.
 * Asked about another tenant, this answers as a tenant nobody administers.
 */
final readonly class ConfigurationAdministratorGroups implements AdministratorGroupsInterface
{
    /**
     * @var list<string>
     */
    private array $groups;

    /**
     * @param list<string> $groups the names of the groups, a name repeated counting once; none for a tenant nobody administers
     *
     * @throws InvalidArgumentException when a name is empty, or begins or ends with whitespace
     */
    public function __construct(
        private string $tenantSlug,
        array $groups = [],
    ) {
        $named = [];
        foreach ($groups as $group) {
            if ($group === '' || trim($group) !== $group) {
                throw new InvalidArgumentException(sprintf(
                    'The group "%s" is named to administer the tenant "%s", and no group carries a name that is empty or begins or ends with whitespace.',
                    $group,
                    $this->tenantSlug,
                ));
            }
            if (! in_array($group, $named, true)) {
                $named[] = $group;
            }
        }
        $this->groups = $named;
    }

    public function groupsAdministering(Tenant $tenant): array
    {
        return $tenant->slug === $this->tenantSlug ? $this->groups : [];
    }
}
