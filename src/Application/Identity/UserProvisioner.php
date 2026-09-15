<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Identity;

use Heidi\Core\Domain\Credential\PasswordCredential;
use Heidi\Core\Domain\Identity\EmailAddress;
use Heidi\Core\Domain\Identity\Subject;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasswordHasherInterface;
use Heidi\Core\Port\UserRepositoryInterface;
use function mb_strlen;
use Psr\Clock\ClockInterface;
use SensitiveParameter;
use function sprintf;
use function strlen;
use Symfony\Component\Uid\Uuid;
use function trim;

/**
 * Creates the users of a tenant and gives them a password: what a console, a fixture or an import does,
 * outside of any request. The writes are the point of the operation, they are made at once. No session is
 * opened, no event is announced: nothing happened to a user yet.
 */
final readonly class UserProvisioner
{
    /**
     * NIST SP 800-63B §5.1.1.2: a memorized secret is at least 8 characters long, counted in characters
     * whatever their encoding.
     */
    public const int MINIMUM_PASSWORD_LENGTH = 8;

    /**
     * The bound Symfony's password hashers apply for the same reason: hashing is meant to be slow, and a
     * password of a few megabytes would make one request cost as much as thousands (CVE-2013-1443).
     */
    public const int MAXIMUM_PASSWORD_BYTES = 4096;

    public function __construct(
        private UserRepositoryInterface $users,
        private CredentialRepositoryInterface $credentials,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * A user who cannot sign in yet: setPassword() gives them a way to. The subject is generated unless the
     * caller brings the one another provider gave (decision 0002: an imported identity keeps the "sub" its
     * clients know); either way it never changes afterwards.
     *
     * @throws UserAlreadyExistsException
     */
    public function create(Tenant $tenant, string $username, ?EmailAddress $email = null, bool $emailVerified = false, ?Subject $subject = null): User
    {
        $username = trim($username);
        if ($this->users->findByUsername($tenant, $username) !== null) {
            throw UserAlreadyExistsException::username($username);
        }
        $subject ??= new Subject(Uuid::v4()->toRfc4122());
        if ($this->users->findBySubject($tenant, $subject) !== null) {
            throw UserAlreadyExistsException::subject($subject->value);
        }
        $user = User::create(Uuid::v7(), $tenant, $subject, $username, $this->clock->now(), $email, $emailVerified);
        $this->users->save($user);

        return $user;
    }

    /**
     * Hashes with the policy of the core and stores the hash on the password credential of the user, created
     * on the first call, replaced on the next ones. The plain password is not kept.
     *
     * @throws InvalidArgumentException when the password is too short or too long
     */
    public function setPassword(User $user, #[SensitiveParameter] string $password): PasswordCredential
    {
        if (mb_strlen($password) < self::MINIMUM_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(sprintf('A password is at least %d characters long.', self::MINIMUM_PASSWORD_LENGTH));
        }
        if (strlen($password) > self::MAXIMUM_PASSWORD_BYTES) {
            throw new InvalidArgumentException(sprintf('A password is at most %d bytes long.', self::MAXIMUM_PASSWORD_BYTES));
        }
        $now = $this->clock->now();
        $hash = $this->hasher->hash($password);
        $credential = $this->credentials->findPasswordCredential($user);
        if ($credential === null) {
            $credential = new PasswordCredential(Uuid::v7(), $user, $hash, $now);
        } else {
            $credential->changePassword($hash, $now);
        }
        $this->credentials->save($credential);

        return $credential;
    }
}
