<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Application\Group\MembershipAlreadyHeldException;
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
 * Finds the person an upstream provider names by its linked identity (provider, subject), never by its
 * address, and creates it at the first sign-in.
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
     * @param array<string, string> $claims by the names of OIDC Core §5.1
     * @param list<mixed> $asserted
     *
     * @throws UpstreamProvisioningRefusedException
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
        // Before the lookup: the linked identity refuses an empty subject, and the person created for it would
        // be left without one.
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
        // Before any write, the snapshot of an identity the console linked included: a disabled person follows the
        // provider no more.
        if (! $user->isEnabled()) {
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::PersonDisabled);
        }
        if (! $provider->isTheSourceOf($user)) {
            $this->writes->atomically(fn (): LinkedIdentity => $this->linker->link($user, $provider->name, $subject, $snapshot));

            return $user;
        }
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
            // Another sign-in gave the address as a username between the check and the unit.
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::AddressHeld);
        } catch (MembershipAlreadyHeldException) {
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::MembershipWrittenMeanwhile);
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
        $profile = $source->rewrite(Profile::empty(), $claims);
        $this->assertAnswersFor($provider, $email);
        $this->assertNobodyHolds($tenant, $email);
        $this->assertNobodyIsNamed($tenant, $email);
        $person = new Subject($this->uuids->opaque()->toRfc4122());
        if ($this->users->findBySubject($tenant, $person) !== null) {
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::DrawnSubjectTaken);
        }
        $now = $this->clock->now();
        // All or none: a person kept without its linked identity is found by no sign-in, yet holds its address.
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
            // Not chained to the caught exception: its message names the person, which this refusal must not tell.
            throw new UpstreamProvisioningRefusedException(UpstreamProvisioningRefusalReason::IdentityLinkedMeanwhile);
        }
        $this->events->dispatch(new PersonProvisioned($now, $tenant, $user->subject, $provider->name, $context));

        return $user;
    }

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
