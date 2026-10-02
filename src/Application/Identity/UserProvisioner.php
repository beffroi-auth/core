<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Credential\PlainPassword;
use Beffroi\Core\Domain\Identity\Address;
use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Domain\Identity\Profile;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
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
        private PasswordHasherInterface $hasher,
        private UuidGeneratorInterface $uuids,
        private ClockInterface $clock,
    ) {
    }

    /**
     * The subject is generated unless an import brings one (decision 0002); it never changes afterwards.
     *
     * @throws UserAlreadyExistsException
     */
    public function create(Tenant $tenant, string $username, ?EmailAddress $email = null, bool $emailVerified = false, ?Subject $subject = null): User
    {
        $username = trim($username);
        if ($this->users->findByUsername($tenant, $username) !== null) {
            throw UserAlreadyExistsException::username($username);
        }
        $subject ??= new Subject($this->uuids->opaque()->toRfc4122());
        if ($this->users->findBySubject($tenant, $subject) !== null) {
            throw UserAlreadyExistsException::subject($subject->value);
        }
        $user = User::create($this->uuids->timeOrdered(), $tenant, $subject, $username, $this->clock->now(), $email, $emailVerified);
        $this->users->save($user);

        return $user;
    }

    /**
     * The claims of OIDC Core §5.1. A replacement, not a merge: what the caller does not say is cleared.
     *
     * @throws InvalidArgumentException when a phone number is absent but said to be verified
     */
    public function setProfile(User $user, Profile $profile, ?string $phoneNumber = null, bool $phoneNumberVerified = false, ?Address $address = null): User
    {
        $now = $this->clock->now();
        // first: the one change the user can refuse
        $user->changePhoneNumber($phoneNumber, $phoneNumberVerified, $now);
        $user->updateProfile($profile, $now);
        $user->changeAddress($address, $now);
        $this->users->save($user);

        return $user;
    }

    /**
     * What a password has to be is the PlainPassword's, checked where one is built; this hashes what it
     * is given and stores it.
     */
    public function setPassword(User $user, #[SensitiveParameter] PlainPassword $password): PasswordCredential
    {
        $now = $this->clock->now();
        $hash = $this->hasher->hash($password->value);
        $credential = $this->credentials->findByUser($user, PasswordCredential::class)[0] ?? null;
        if ($credential === null) {
            $credential = new PasswordCredential($this->uuids->timeOrdered(), $user, $hash, $now);
        } else {
            $credential->changePassword($hash, $now);
        }
        $this->credentials->save($credential);

        return $credential;
    }
}
