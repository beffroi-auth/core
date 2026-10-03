<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Event\PersonDescribedThemselves;
use Beffroi\Core\Domain\Identity\Claim\ClaimInterface;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A person says something of themselves (issue #452), when the tenant lets them.
 *
 * **The policy is read here and not by the page.** A page that only hides a field it must not offer is a
 * page whose form can be sent anyway, so what refuses is the use case, on the server, for every caller:
 * the browser, a command, whatever comes next. What the page does with the policy is show a person why a
 * field is not theirs to change.
 *
 * It announces the act and not the value (PersonDescribedThemselves), the value living in the row of that
 * person and nowhere else.
 */
final readonly class SelfDescription
{
    public function __construct(
        private UserRepositoryInterface $users,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws InvalidArgumentException when this tenant does not open that claim, or the value is not one
     *                                  the claim holds
     */
    public function describe(User $user, ClaimInterface $claim, ?string $value, RequestContext $context): void
    {
        $user->tenant->selfServedClaims->assertOpens($claim->name());
        $now = $this->clock->now();
        $claim->describe($user, $value, $now);
        $this->users->save($user);
        $this->events->dispatch(new PersonDescribedThemselves($user, $claim->name(), $value === null, $now, $context));
    }
}
