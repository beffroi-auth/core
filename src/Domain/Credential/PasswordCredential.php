<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Symfony\Component\Uid\Uuid;

/**
 * A password: what a user knows, "pwd" (RFC 8176 §2). Replaced, never revoked.
 */
final class PasswordCredential extends Credential implements ProvingCredentialInterface
{
    /**
     * How long a password goes before the list of compromised passwords is asked about it again.
     *
     * Thirty days, because the lists are published in batches and a password that was clean yesterday is
     * not found tomorrow by asking faster: one lookup a month per person who signs in is what the range
     * endpoint is sized for, and a login that asked every time would be a login that waits on somebody
     * else's service to say what it already knew.
     */
    public const string LOOKUP_AGAIN_AFTER = 'P30D';

    public private(set) DateTimeImmutable $hashChangedAt;

    /**
     * When the list of compromised passwords was last asked about this one, null while it never was.
     */
    public private(set) ?DateTimeImmutable $breachCheckedAt = null;

    /**
     * When it was found there, null while it was not. It outlives the login that found it, which is the
     * point: a person is told on the pages of their account and not in the one request that noticed.
     */
    public private(set) ?DateTimeImmutable $breachFoundAt = null;

    public function __construct(
        Uuid $id,
        User $user,
        public private(set) PasswordHash $hash,
        DateTimeImmutable $createdAt,
    ) {
        parent::__construct($id, $user, $createdAt);
        $this->hashChangedAt = $createdAt;
    }

    public function kind(): string
    {
        return 'password';
    }

    /**
     * What a password proves, whoever holds one: the kind says it of itself, so that what a tenant admits
     * can be asked before a credential of this kind exists (issue #327), as TotpCredential::method() is.
     */
    public static function method(): AuthenticationMethod
    {
        return AuthenticationMethod::password();
    }

    public function provableMethods(): array
    {
        return [self::method()];
    }

    /**
     * The password was just verified and signed its user in. Every kind says what a use of it does to it;
     * a password has nothing to move forward but the date, and a login says so here rather than reaching
     * for the date itself.
     */
    public function recordUse(DateTimeImmutable $now): void
    {
        $this->markUsed($now);
    }

    /**
     * A stronger hash of the same password. What the lists say of it is unchanged, the password being
     * unchanged.
     */
    public function rehash(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
    }

    /**
     * Another password; what happens to the sessions is the caller's policy.
     *
     * **What the lists said is forgotten here**, because they said it of the password that is gone: a new
     * password carries no mark, and the use case that wrote it says what it had asked about this one.
     */
    public function changePassword(PasswordHash $hash, DateTimeImmutable $now): void
    {
        $this->hash = $hash;
        $this->hashChangedAt = $now;
        $this->breachCheckedAt = null;
        $this->breachFoundAt = null;
    }

    /**
     * The list was asked and does not hold this password.
     */
    public function notFoundInABreach(DateTimeImmutable $now): void
    {
        $this->breachCheckedAt = $now;
        $this->breachFoundAt = null;
    }

    /**
     * The list holds it: somebody else has the plaintext of the password this user signs in with.
     */
    public function foundInABreach(DateTimeImmutable $now): void
    {
        $this->breachCheckedAt = $now;
        $this->breachFoundAt = $now;
    }

    public function isBreached(): bool
    {
        return $this->breachFoundAt !== null;
    }

    /**
     * Whether a list is to be asked about this password now: one that never was, or one whose last answer
     * is older than the delay above. A login does not ask again what was asked yesterday.
     */
    public function dueForABreachLookup(DateTimeImmutable $now): bool
    {
        if ($this->breachCheckedAt === null) {
            return true;
        }

        return $this->breachCheckedAt->add(new DateInterval(self::LOOKUP_AGAIN_AFTER)) <= $now;
    }

    /**
     * A password is held as the digest of what was presented, with the algorithm it was computed with and the
     * parameters it was computed under.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            ...parent::classifiedFields(),
            'hash' => new NoPersonalDatum(Reason::HASHED . ', with the algorithm it was computed with and the parameters it was computed under'),
            'hashChangedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'breachCheckedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'breachFoundAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
        ];
    }
}
