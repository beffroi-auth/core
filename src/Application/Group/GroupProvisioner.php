<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use Beffroi\Core\Domain\Event\GroupCreated;
use Beffroi\Core\Domain\Group\Group;
use Beffroi\Core\Domain\Group\GroupSource;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\GroupAlreadyExistsException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\GroupRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Takes the name as it is said, never trimmed or folded: the mapping of a provider names it exactly.
 */
final readonly class GroupProvisioner
{
    public function __construct(
        private GroupRepositoryInterface $groups,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws GroupAlreadyExistsException
     */
    public function create(Tenant $tenant, string $name, RequestContext $context): Group
    {
        return $this->createOnce($tenant, $name, $context, null);
    }

    /**
     * The act names the administrator (decision 0038).
     *
     * @throws InvalidArgumentException
     * @throws GroupAlreadyExistsException
     */
    public function createByAnAdministrator(Tenant $tenant, string $name, Subject $administrator, RequestContext $context): Group
    {
        return $this->createOnce($tenant, $name, $context, $administrator);
    }

    /**
     * @throws InvalidArgumentException
     * @throws GroupAlreadyExistsException
     */
    private function createOnce(Tenant $tenant, string $name, RequestContext $context, ?Subject $administrator): Group
    {
        if ($this->groups->findByName($tenant, $name) !== null) {
            throw GroupAlreadyExistsException::named($name);
        }
        $now = $this->clock->now();
        $group = new Group($this->uuids->timeOrdered(), $tenant, $name, GroupSource::Local, $now);
        $this->groups->save($group);
        $this->events->dispatch(new GroupCreated($tenant, $group->name, $now, $context, $administrator));

        return $group;
    }
}
