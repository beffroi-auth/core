<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Application\Group\UpstreamMemberships;
use Beffroi\Core\Domain\Event\PersonProvisioned;
use Beffroi\Core\Domain\Group\GroupMapping;
use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\EmailDomain;
use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Identity\Profile;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\UpstreamProvider;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\LinkedIdentityRepositoryInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The person an upstream provider names at a sign-in through it, created at the first one (decision 0002):
 * what the sign-in of a protocol asks once it has verified what the provider said, and before it opens a
 * session.
 *
 * **The person is found by the linked identity (provider, subject), never by the address.** When no linked
 * identity names that subject, the person is created, with its source, which names the provider and holds
 * the address and those of the claims of the profile it fills that it said, and its linked identity, with
 * the snapshot of what the provider said; `PersonProvisioned` is announced. Its subject is drawn as any
 * other, and its username is its address, which is what the pages a person reads call them by. Its address
 * is verified, the provider answering for its domain. The next sign-ins create nothing and announce nothing:
 * the snapshot is refreshed, and what the source holds becomes exactly what the provider fills and says now,
 * written as it says it, a claim it no longer fills or says being released to a manual description with the
 * value it wrote last. **The username follows the address**: a provider that sends another one has the
 * person renamed to it in the same unit, so that the old address is nobody's username any more and the next
 * person who holds it can be created. Only by the provider that created the person: an identity an import
 * linked to a local person, or to a person another provider created, refreshes its snapshot and nothing
 * else.
 *
 * **What a provider writes of a person keeps two rules**, whether it creates the person or refreshes it. The
 * address is in a domain the provider declares, so that a provider never creates or rewrites a person of a
 * domain it does not answer for (decision 0029). And no other person of the tenant holds that address, as
 * its mailbox or as its username, so that no existing account is linked on its own, linking one being spike
 * 12's after a local re-authentication; at a refresh, each is asked only of what changed, the person already
 * holding their own mailbox and their own name.
 *
 * **Every refusal is raised before the writes, and says nothing of who holds what**
 * (`UpstreamProvisioningRefusedException`, one message, the reason for the audit trail). The writes of a
 * sign-in are one unit (`AtomicWritesInterface`): the person, its source and its linked identity are all
 * kept, or none is, and the event is announced once they are. The one collision no check before the unit can
 * see, another sign-in linking the same subject in between, is answered by the same refusal once the unit
 * is taken back, without what the linker said of whom. A sign-in that loses a race at the store, the person or its
 * linked identity written by another sign-in between the checks and the unit, is answered the same way: the store
 * turns the violation of its unique constraint into the exception of the port it went through
 * (`UserAlreadyExistsException`, `IdentityAlreadyLinkedException`), and the unit that raised it is taken back.
 *
 * **The groups and the telephone number of a sign-in are written in the same unit** (beffroi/saml S-56): the
 * memberships the provider asserts are replaced (`UpstreamMemberships::replace()`), and a telephone number it said
 * is written unverified, held by the source as a claim the provider fills, and released when it says none; both of a
 * person this provider is the source of, never of one an import linked. The event is announced once all of it is kept.
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
        private UuidGeneratorInterface $uuids,
        private UpstreamMemberships $memberships,
    ) {
    }

    /**
     * @param string $subject the subject the provider gave, compared byte for byte
     * @param array<string, string> $claims what the provider said of the person, by the names of OIDC Core
     *                                      §5.1; the address is given apart, its domain being what is read
     * @param string|null $phoneNumber the telephone number it said, which is no claim of the profile, null when it
     *                                 said none
     * @param GroupMapping|null $groups the groups of the tenant the values it may assert stand for, null when its
     *                                  declaration maps none
     * @param list<mixed> $asserted the values of its groups attribute, as it sent them
     *
     * @throws UpstreamProvisioningRefusedException when the provider may not create or write that person
     * @throws InvalidArgumentException when the subject is empty, or a claim is said in a form the profile refuses
     */
    public function personNamedBy(
        Tenant $tenant,
        UpstreamProvider $provider,
        string $subject,
        EmailAddress $email,
        array $claims,
        RequestContext $context,
        ?string $phoneNumber = null,
        ?GroupMapping $groups = null,
        array $asserted = [],
    ): User {
        // before the lookup: an empty subject matches no row, and the person created for it would be left
        // without the linked identity that refuses it
        if ($subject === '') {
            throw new InvalidArgumentException('A person an upstream provider names carries the subject it gave.');
        }
        $snapshot = [...$claims, UpstreamProvider::EMAIL => $email->value];
        if ($phoneNumber !== null) {
            $snapshot[UpstreamProvider::PHONE_NUMBER] = $phoneNumber;
        }
        $linked = $this->identities->find($tenant, $provider->name, $subject);
        if ($linked === null) {
            return $this->created($tenant, $provider, $subject, $email, $claims, $snapshot, $context, $phoneNumber, $groups, $asserted);
        }
        $user = $linked->user;
        if (! $provider->isTheSourceOf($user)) {
            $this->writes->atomically(fn (): LinkedIdentity => $this->linker->link($user, $provider->name, $subject, $snapshot));

            return $user;
        }
        // every refusal before the unit: a profile the claims cannot be, a domain the provider does not
        // answer for, a mailbox or a username somebody else holds
        $source = $provider->source($claims, $phoneNumber !== null);
        $profile = $source->rewrite($user->profile, $claims);
        $this->assertAnswersFor($provider, $email);
        if (! ($user->email?->isTheSameMailboxAs($email) ?? false)) {
            $this->assertNobodyHolds($tenant, $email, $user);
        }
        $renamed = $user->username !== $email->value;
        if ($renamed) {
            $this->assertNobodyIsNamed($tenant, $email, $user);
        }
        $now = $this->clock->now();
        try {
            $this->writes->atomically(function () use (
                $user,
                $provider,
                $subject,
                $email,
                $source,
                $profile,
                $snapshot,
                $now,
                $renamed,
                $phoneNumber,
                $groups,
                $asserted,
            ): void {
                $user->heldBy($source, $now);
                $user->changeEmail($email, true, $now);
                if ($renamed) {
                    $user->rename($email->value, $now);
                }
                $user->updateProfile($profile, $now);
                $this->writePhoneNumber($user, $phoneNumber, $now);
                $this->users->save($user);
                $this->linker->link($user, $provider->name, $subject, $snapshot);
                $this->writeGroups($user, $groups, $asserted);
            });
        } catch (UserAlreadyExistsException) {
            // another sign-in gave the address as a name between the check and the unit
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::AddressHeld);
        }

        return $user;
    }

    /**
     * @param array<string, string> $claims
     * @param array<string, string> $snapshot
     * @param list<mixed> $asserted
     *
     * @throws UpstreamProvisioningRefusedException
     */
    private function created(
        Tenant $tenant,
        UpstreamProvider $provider,
        string $subject,
        EmailAddress $email,
        array $claims,
        array $snapshot,
        RequestContext $context,
        ?string $phoneNumber,
        ?GroupMapping $groups,
        array $asserted,
    ): User {
        $source = $provider->source($claims, $phoneNumber !== null);
        // every refusal before the unit: a profile the claims cannot be, a domain the provider does not
        // answer for, an address somebody holds as a mailbox or as a username, a subject drawn twice
        $profile = $source->rewrite(Profile::empty(), $claims);
        $this->assertAnswersFor($provider, $email);
        $this->assertNobodyHolds($tenant, $email);
        $this->assertNobodyIsNamed($tenant, $email);
        $person = new Subject($this->uuids->opaque()->toRfc4122());
        if ($this->users->findBySubject($tenant, $person) !== null) {
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::DrawnSubjectTaken);
        }
        $now = $this->clock->now();
        // the person, its source and its linked identity, all or none: a person kept without the identity
        // that names it is a person no sign-in finds, whose address refuses every creation after it
        try {
            $user = $this->writes->atomically(function () use (
                $tenant,
                $provider,
                $subject,
                $email,
                $source,
                $profile,
                $snapshot,
                $now,
                $person,
                $phoneNumber,
                $groups,
                $asserted,
            ): User {
                $user = $this->provisioner->create($tenant, $email->value, $email, true, $person, $source);
                $user->updateProfile($profile, $now);
                $this->writePhoneNumber($user, $phoneNumber, $now);
                $this->users->save($user);
                $this->linker->link($user, $provider->name, $subject, $snapshot);
                $this->writeGroups($user, $groups, $asserted);

                return $user;
            });
        } catch (IdentityAlreadyLinkedException|UserAlreadyExistsException) {
            // without the exception it answers: it names the person the identity is, which is not the
            // business of whoever reads this refusal; the person written by another sign-in between the
            // checks and the unit, which the store says by the second, is the same race
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::IdentityLinkedMeanwhile);
        }
        // announced once it is kept, never for a person a failed write took back
        $this->events->dispatch(new PersonProvisioned($now, $tenant, $user->subject, $provider->name, $context));

        return $user;
    }

    /**
     * S-45 of beffroi/saml: a telephone number a provider fills is never verified by being filled; the number the
     * person already holds is left as it is, verified or not.
     */
    private function writePhoneNumber(User $user, ?string $phoneNumber, DateTimeImmutable $now): void
    {
        if ($phoneNumber !== null && $phoneNumber !== $user->phoneNumber) {
            $user->changePhoneNumber($phoneNumber, false, $now);
        }
    }

    /**
     * @param list<mixed> $asserted
     */
    private function writeGroups(User $user, ?GroupMapping $groups, array $asserted): void
    {
        if ($groups !== null) {
            $this->memberships->replace($user, $asserted, $groups);
        }
    }

    /**
     * The domain of the address read by EmailDomain, the rule the login page and the refusal of a local credential
     * read it by too (decision 0029): one the rule does not read is no domain the provider answers for.
     *
     * @throws UpstreamProvisioningRefusedException
     */
    private function assertAnswersFor(UpstreamProvider $provider, EmailAddress $email): void
    {
        try {
            $domain = EmailDomain::ofAddress($email->value);
        } catch (InvalidArgumentException) {
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::OutsideItsDomains);
        }
        if (! $provider->answersFor($domain)) {
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::OutsideItsDomains);
        }
    }

    /**
     * Whoever holds that mailbox, all of them, and none of them the person it is written for: which holder a
     * store hands back first says nothing here.
     *
     * @throws UpstreamProvisioningRefusedException
     */
    private function assertNobodyHolds(Tenant $tenant, EmailAddress $email, ?User $except = null): void
    {
        foreach ($this->users->findAllByEmail($tenant, $email) as $holder) {
            if ($except === null || ! $holder->id->equals($except->id)) {
                throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::AddressHeld);
            }
        }
    }

    /**
     * Whoever is named by that address, as it is written, a username being matched byte for byte, and not the
     * person it is written for.
     *
     * @throws UpstreamProvisioningRefusedException
     */
    private function assertNobodyIsNamed(Tenant $tenant, EmailAddress $email, ?User $except = null): void
    {
        $named = $this->users->findByUsername($tenant, $email->value);
        if ($named !== null && ($except === null || ! $named->id->equals($except->id))) {
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::AddressHeld);
        }
    }
}
