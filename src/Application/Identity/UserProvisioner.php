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
use Beffroi\Core\Port\ClaimsInterface;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use function trim;

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
        private ClaimsInterface $claims,
    ) {
    }

    /**
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
     * A replacement, not a merge: what the caller does not say is cleared, save the claims the source holds.
     *
     * @throws InvalidArgumentException when a claim the source holds is said otherwise, a claim has a value it does
     *                                  not hold, or a phone number is absent but said to be verified
     */
    public function setProfile(User $user, Profile $profile, ?string $phoneNumber = null, bool $phoneNumberVerified = false, ?Address $address = null): User
    {
        $source = $user->source;
        $address = $address?->isEmpty() === true ? null : $address;
        $source->assertLeaves(self::changedBy($user, $profile, $phoneNumber, $phoneNumberVerified, $address));
        foreach ($profile->claims() as $name => $value) {
            $this->claims->named($name)?->field()->assertHolds($value);
        }
        $now = $this->clock->now();
        // The one change the user can refuse comes first.
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
     * @throws PasswordRefusedException when a list counts this password, or could not be asked
     * @throws CredentialEnrolmentFailedException when the tenant's floor admits no password, or the person's
     *                                             domain is federated
     */
    public function setPassword(User $user, #[SensitiveParameter] PlainPassword $password): PasswordCredential
    {
        $this->enrolments->assertTheFloorAdmits($user->tenant, 'password', [PasswordCredential::method()]);
        $this->enrolments->assertTheirDomainAdmits($user, 'password');
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
