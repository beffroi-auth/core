<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Event\PersonDescribedThemselves;
use Beffroi\Core\Domain\Identity\Claim\ClaimInterface;
use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\ClaimHistoryInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;
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
 *
 * **What the claim used to say is written here and not on the lane** (issue #482, ClaimChange). The row of
 * the person and the history of its change commit together or the record changed and its history did not,
 * which is a hole in the one thing a history is for; so this writes both, in the unit of work it already
 * has. Nothing is written when nothing changed: saying again what a claim already said is an act, which
 * the journal has, and not a change.
 */
final readonly class SelfDescription
{
    public function __construct(
        private UserRepositoryInterface $users,
        private ClaimHistoryInterface $history,
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
        $was = $claim->of($user);
        $claim->describe($user, $value, $now);
        $became = $claim->of($user);
        $this->users->save($user);
        // nothing is written when nothing changed: saying again what a claim already said is an act and
        // not a change, so the journal has it and the history does not
        if ($was !== $became) {
            $this->history->record(new ClaimChange(Uuid::v7(), $user, $claim->name(), $was, $became, $now, $context));
        }
        $this->events->dispatch(new PersonDescribedThemselves($user, $claim->name(), $became === null, $now, $context));
    }
}
