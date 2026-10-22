<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Application\Credential\ActivePassword;
use Beffroi\Core\Application\Credential\BreachedPasswordRefusal;
use Beffroi\Core\Application\Credential\EnrolmentRefusal;
use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Credential\PlainPassword;
use Beffroi\Core\Domain\Identity\Address;
use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\Profile;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Identity\UserSource;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\PasswordRefusedException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use function trim;

/**
 * Creates the users of a tenant and gives them a password, from a console, a fixture or an import.
 * Synchronous writes, no session, no event.
 */
final readonly class UserProvisioner
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CredentialRepositoryInterface $credentials,
        private ActivePassword $activePassword,
        private PasswordHasherInterface $hasher,
        private BreachedPasswordRefusal $breaches,
        private EnrolmentRefusal $enrolments,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * The subject is generated unless an import brings one (decision 0002); it never changes afterwards. The
     * person is local unless the caller names the upstream provider that creates it and what it holds.
     *
     * @throws UserAlreadyExistsException
     */
    public function create(
        Tenant $tenant,
        string $username,
        ?EmailAddress $email = null,
        bool $emailVerified = false,
        ?Subject $subject = null,
        ?UserSource $source = null,
    ): User {
        $username = trim($username);
        if ($this->users->findByUsername($tenant, $username) !== null) {
            throw UserAlreadyExistsException::username($username);
        }
        $subject ??= new Subject($this->uuids->opaque()->toRfc4122());
        if ($this->users->findBySubject($tenant, $subject) !== null) {
            throw UserAlreadyExistsException::subject($subject->value);
        }
        $user = User::create($this->uuids->timeOrdered(), $tenant, $subject, $username, $this->clock->now(), $email, $emailVerified, $source);
        $this->users->save($user);

        return $user;
    }

    /**
     * The claims of OIDC Core §5.1. A replacement, not a merge: what the caller does not say is cleared,
     * except what the source of the person holds, which stays what that source wrote (decision 0002).
     *
     * **A claim the source holds is not the caller's to change.** One the caller says otherwise is refused,
     * naming the provider, before anything is written; one it says as it already is, it may say, which is
     * what a script played again does.
     *
     * @throws InvalidArgumentException when the source holds a claim the caller says otherwise, or a phone
     *                                  number is absent but said to be verified
     */
    public function setProfile(User $user, Profile $profile, ?string $phoneNumber = null, bool $phoneNumberVerified = false, ?Address $address = null): User
    {
        $source = $user->source;
        $address = $address?->isEmpty() === true ? null : $address;
        $source->assertLeaves(self::changedBy($user, $profile, $phoneNumber, $phoneNumberVerified, $address));
        $now = $this->clock->now();
        // first: the one change the user can refuse
        if (! $source->holds('phone_number')) {
            $user->changePhoneNumber($phoneNumber, $phoneNumberVerified, $now);
        }
        $kept = [];
        foreach ($user->profile->claims() as $claim => $value) {
            if ($source->holds($claim)) {
                $kept[$claim] = $value;
            }
        }
        $user->updateProfile($kept === [] ? $profile : $profile->describedWith($kept), $now);
        if (! $source->holds('address')) {
            $user->changeAddress($address, $now);
        }
        $this->users->save($user);

        return $user;
    }

    /**
     * The claims a description says otherwise than the person holds them: what it does not say is not
     * among them, a claim the source holds being kept rather than cleared.
     *
     * @return list<string>
     */
    private static function changedBy(User $user, Profile $profile, ?string $phoneNumber, bool $phoneNumberVerified, ?Address $address): array
    {
        $changed = [];
        $was = $user->profile->claims();
        foreach ($profile->claims() as $claim => $value) {
            if ($value !== null && $value !== $was[$claim]) {
                $changed[] = $claim;
            }
        }
        if (($phoneNumber !== null || $phoneNumberVerified) && ($phoneNumber !== $user->phoneNumber || $phoneNumberVerified !== $user->phoneNumberVerified)) {
            $changed[] = 'phone_number';
        }
        if ($address !== null && $address->toDocument() !== $user->address?->toDocument()) {
            $changed[] = 'address';
        }

        return $changed;
    }

    /**
     * What a password has to be on its own is the PlainPassword's, checked where one is built; what a list
     * of compromised passwords says of it is asked here, before anything is hashed, because a lookup is
     * input and output and a value object does none.
     *
     * The answer is written on the credential when there was one, so that the first login does not ask
     * again what was just asked.
     *
     * @throws PasswordRefusedException when a list counts this password, or could not be asked
     * @throws CredentialEnrolmentFailedException when the floor of the tenant admits no password (issue #327)
     */
    public function setPassword(User $user, #[SensitiveParameter] PlainPassword $password): PasswordCredential
    {
        // before the list is asked and before anything is hashed: a tenant that holds no password has
        // nobody to ask about this one
        $this->enrolments->assertTheFloorAdmits($user->tenant, 'password', [PasswordCredential::method()]);
        $count = $this->breaches->count($password);
        $now = $this->clock->now();
        $hash = $this->hasher->hash($password->value);
        $credential = $this->activePassword->of($user);
        if ($credential === null) {
            $credential = new PasswordCredential($this->uuids->timeOrdered(), $user, $hash, $now);
        } else {
            $credential->changePassword($hash, $now);
        }
        if ($count->wasAsked()) {
            $credential->notFoundInABreach($now);
        }
        $this->credentials->save($credential);

        return $credential;
    }
}
