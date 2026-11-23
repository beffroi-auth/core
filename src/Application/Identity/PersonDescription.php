<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use function array_key_exists;
use function array_keys;
use Beffroi\Core\Application\Identity\Member\ClaimMember;
use Beffroi\Core\Application\Identity\Member\MemberInterface;
use Beffroi\Core\Domain\Event\PersonDescribed;
use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ClaimHistoryInterface;
use Beffroi\Core\Port\ClaimsInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * What an administrator of the tenant says of a person, written whole or not at all (decision 0038): each value that
 * changed is a row of the history of the person, and the description one act of theirs, both naming the administrator.
 * What the upstream source of the person holds stays its own.
 */
final readonly class PersonDescription
{
    /**
     * @var array<string, MemberInterface>
     */
    private array $members;

    /**
     * @param iterable<MemberInterface> $members what an administrator says of a person beside the claims a person says of themselves, in the order they are said
     */
    public function __construct(
        private ClaimsInterface $claims,
        iterable $members,
        private UserRepositoryInterface $users,
        private ClaimHistoryInterface $history,
        private AtomicWritesInterface $writes,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
        $byName = [];
        foreach ($members as $member) {
            $byName[$member->name()] = $member;
        }
        $this->members = $byName;
    }

    /**
     * @param array<string, mixed> $said the value of each member said, by its name in the document of a person
     *
     * @throws InvalidArgumentException  when a member is not one an administrator says, a value is not one it holds, or it changes what the source of the person holds
     * @throws UserAlreadyExistsException when another person of the tenant has the username said
     *
     * @return list<string> the names of what changed, none when every value was said as it is
     */
    public function describeByAnAdministrator(User $user, array $said, Subject $administrator, RequestContext $context): array
    {
        $members = $this->membersSaid($said);
        $before = $this->read($user, $members);
        $now = $this->clock->now();
        $changed = $this->writes->atomically(function () use ($user, $said, $members, $before, $now, $context, $administrator): array {
            foreach ($members as $member) {
                $member->say($user, $said[$member->name()], $now);
            }
            $user->source->assertLeaves($this->heldBySource($user, $members, $before));
            $this->users->save($user);
            $changed = [];
            foreach ($this->read($user, $members) as $name => $became) {
                $was = $before[$name] ?? null;
                if ($was !== $became) {
                    $this->history->record(new ClaimChange(Uuid::v7(), $user, $name, $was, $became, $now, $context, $administrator));
                    $changed[] = $name;
                }
            }

            return $changed;
        });
        if ($changed !== []) {
            $this->events->dispatch(new PersonDescribed($user->tenant, $user->subject, $changed, $now, $context, $administrator));
        }

        return $changed;
    }

    /**
     * The claims a person says of themselves first, then the other members in the order they are wired, so that an
     * address is written before whether it was verified.
     *
     * @param array<string, mixed> $said
     *
     * @throws InvalidArgumentException when a member is not one an administrator says
     *
     * @return list<MemberInterface>
     */
    private function membersSaid(array $said): array
    {
        $claims = [];
        foreach (array_keys($said) as $name) {
            if (array_key_exists($name, $this->members)) {
                continue;
            }
            $claim = $this->claims->named($name);
            if ($claim === null) {
                throw new InvalidArgumentException(sprintf('An administrator says no "%s" of a person.', $name));
            }
            $claims[] = new ClaimMember($claim);
        }
        $others = [];
        foreach ($this->members as $name => $member) {
            if (array_key_exists($name, $said)) {
                $others[] = $member;
            }
        }

        return [...$claims, ...$others];
    }

    /**
     * What the source of the person would hold of each member that changed: a member said as it is changes nothing, so
     * the source has nothing to leave of it.
     *
     * @param list<MemberInterface>      $said
     * @param array<string, string|null> $before
     *
     * @return list<string>
     */
    private function heldBySource(User $user, array $said, array $before): array
    {
        $held = [];
        foreach ([...$said, ...$this->members] as $member) {
            foreach ($member->of($user) as $name => $value) {
                if (($before[$name] ?? null) !== $value) {
                    $held[] = $member->heldAs();

                    break;
                }
            }
        }

        return $held;
    }

    /**
     * What the members said and every other member say now, so that a change a member makes to another, an address
     * taking back its verification, is written down too.
     *
     * @param list<MemberInterface> $said
     *
     * @return array<string, string|null>
     */
    private function read(User $user, array $said): array
    {
        $read = [];
        foreach ([...$said, ...$this->members] as $member) {
            foreach ($member->of($user) as $name => $value) {
                $read[$name] = $value;
            }
        }

        return $read;
    }
}
