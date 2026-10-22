<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Event\PersonProvisioned;
use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\Profile;
use Beffroi\Core\Domain\Identity\UpstreamProvider;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\LinkedIdentityRepositoryInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The person an upstream provider names at a sign-in through it, created at the first one (decision 0002):
 * what the sign-in of a protocol asks once it has verified what the provider said, and before it opens a
 * session.
 *
 * **The person is found by the linked identity (provider, subject), never by the address.** When no linked
 * identity names that subject, the person is created, with its source, the provider that holds the address
 * and those of the claims of the profile it fills that it said, and its linked identity, with the snapshot of what the provider
 * said; `PersonProvisioned` is announced. Its username is its address, its subject is drawn as any other,
 * and its address is verified, the provider answering for its domain. The next sign-ins create nothing and
 * announce nothing: the snapshot is refreshed, and what the source holds becomes exactly what the provider
 * fills and says now, written as it says it, a claim it no longer fills or says being released to a manual
 * description with the value it wrote last. Only by the provider that created the person: an identity an import linked to a local
 * person, or to a person another provider created, refreshes its snapshot and nothing else.
 *
 * **What a provider writes of a person keeps two rules**, whether it creates the person or refreshes it, and
 * a rule broken is a refusal raised before anything is written. The address is in a domain the provider
 * declares, so that a provider never creates or rewrites a person of a domain it does not answer for
 * (decision 0029). And no other person of the tenant holds the address, so that no existing account is
 * linked on its own, linking one being spike 12's after a local re-authentication; at a refresh, that one
 * is asked only when the address changed, the person already holding their own.
 *
 * **The writes of a sign-in are one unit** (`AtomicWritesInterface`): the person, its source and its linked
 * identity are all kept, or none is, and the event is announced once they are.
 */
final readonly class JustInTimeProvisioning
{
    public function __construct(
        private LinkedIdentityRepositoryInterface $identities,
        private UserRepositoryInterface $users,
        private UserProvisioner $provisioner,
        private IdentityLinker $linker,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
        private AtomicWritesInterface $writes,
    ) {
    }

    /**
     * @param string $subject the subject the provider gave, compared byte for byte
     * @param array<string, string> $claims what the provider said of the person, by the names of OIDC Core
     *                                      §5.1; the address is given apart, its domain being what is read
     *
     * @throws UpstreamProvisioningRefusedException when the address is outside the domains of the provider,
     *                                              or another person of the tenant holds it
     * @throws UserAlreadyExistsException when a person of the tenant already has the address as username
     * @throws InvalidArgumentException when the subject is empty, or a claim is said in a form the profile refuses
     */
    public function personNamedBy(
        Tenant $tenant,
        UpstreamProvider $provider,
        string $subject,
        EmailAddress $email,
        array $claims,
        RequestContext $context,
    ): User {
        // before the lookup: an empty subject matches no row, and the person created for it would be left
        // without the linked identity that refuses it
        if ($subject === '') {
            throw new InvalidArgumentException('A person an upstream provider names carries the subject it gave.');
        }
        $snapshot = [...$claims, UpstreamProvider::EMAIL => $email->value];
        $linked = $this->identities->find($tenant, $provider->name, $subject);
        if ($linked === null) {
            return $this->created($tenant, $provider, $subject, $email, $claims, $snapshot, $context);
        }
        $user = $linked->user;
        $this->writes->atomically(function () use ($user, $provider, $subject, $email, $claims, $snapshot): void {
            if ($provider->isTheSourceOf($user)) {
                $this->rewritten($user, $provider, $email, $claims);
            }
            $this->linker->link($user, $provider->name, $subject, $snapshot);
        });

        return $user;
    }

    /**
     * @param array<string, string> $claims
     * @param array<string, string> $snapshot
     */
    private function created(
        Tenant $tenant,
        UpstreamProvider $provider,
        string $subject,
        EmailAddress $email,
        array $claims,
        array $snapshot,
        RequestContext $context,
    ): User {
        $source = $provider->source($claims);
        // every refusal before the first write: a profile the claims cannot be, a domain the provider does
        // not answer for, an address somebody holds
        $profile = $source->rewrite(Profile::empty(), $claims);
        $this->assertAnswersFor($provider, $email);
        if ($this->users->findByEmail($tenant, $email) !== null) {
            throw UpstreamProvisioningRefusedException::heldByAnotherPerson($provider->name);
        }
        $now = $this->clock->now();
        // the person, its source and its linked identity, all or none: a person kept without the identity
        // that names it is a person no sign-in finds, whose address refuses every creation after it
        $user = $this->writes->atomically(function () use ($tenant, $provider, $subject, $email, $source, $profile, $snapshot, $now): User {
            $user = $this->provisioner->create($tenant, $email->value, $email, true, source: $source);
            $user->updateProfile($profile, $now);
            $this->users->save($user);
            $this->linker->link($user, $provider->name, $subject, $snapshot);

            return $user;
        });
        // announced once it is kept, never for a person a failed write took back
        $this->events->dispatch(new PersonProvisioned($now, $tenant, $user->subject, $provider->name, $context));

        return $user;
    }

    /**
     * What the source of the person holds becomes exactly what its provider fills and says now, each claim
     * written as it says it: a claim it no longer fills or says is released, keeping the value it wrote
     * last, which a manual description may then say.
     *
     * @param array<string, string> $claims
     */
    private function rewritten(User $user, UpstreamProvider $provider, EmailAddress $email, array $claims): void
    {
        $source = $provider->source($claims);
        $profile = $source->rewrite($user->profile, $claims);
        $this->assertAnswersFor($provider, $email);
        $this->assertNobodyElseHolds($user, $provider, $email);
        $now = $this->clock->now();
        $user->heldBy($source, $now);
        $user->changeEmail($email, true, $now);
        $user->updateProfile($profile, $now);
        $this->users->save($user);
    }

    /**
     * @throws UpstreamProvisioningRefusedException
     */
    private function assertAnswersFor(UpstreamProvider $provider, EmailAddress $email): void
    {
        if (! $provider->answersFor($email)) {
            throw UpstreamProvisioningRefusedException::outsideItsDomains($provider->name, $email);
        }
    }

    /**
     * Asked only of an address that changed: the person holds the one they have, and another person who
     * shares it with them is not a reason to refuse them their own sign-in.
     *
     * @throws UpstreamProvisioningRefusedException
     */
    private function assertNobodyElseHolds(User $user, UpstreamProvider $provider, EmailAddress $email): void
    {
        if ($user->email?->equals($email) === true) {
            return;
        }
        $holder = $this->users->findByEmail($user->tenant, $email);
        if ($holder !== null && ! $holder->id->equals($user->id)) {
            throw UpstreamProvisioningRefusedException::heldByAnotherPerson($provider->name);
        }
    }
}
