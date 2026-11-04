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
 * A person says one claim of themselves. What the tenant does not open, or the source of the person holds, is
 * refused here and not by the page, for every caller.
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
     * @throws InvalidArgumentException when the claim is not the person's to say, or the value is not one it holds
     */
    public function describe(User $user, ClaimInterface $claim, ?string $value, RequestContext $context): void
    {
        $user->tenant->selfServedClaims->assertOpens($claim->name());
        $user->source->assertLeaves([$claim->name()]);
        $now = $this->clock->now();
        $was = $claim->of($user);
        $claim->describe($user, $value, $now);
        $became = $claim->of($user);
        $this->users->save($user);
        // Saying a claim again is an act the journal keeps, not a change the history keeps.
        if ($was !== $became) {
            $this->history->record(new ClaimChange(Uuid::v7(), $user, $claim->name(), $was, $became, $now, $context));
        }
        $this->events->dispatch(new PersonDescribedThemselves($user, $claim->name(), $became === null, $now, $context));
    }
}
