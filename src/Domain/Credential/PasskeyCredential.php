<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use function array_values;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Exception\InvalidArgumentException;
use function mb_strlen;
use function sprintf;
use Symfony\Component\Uid\Uuid;
use function trim;
use Webauthn\AuthenticatorData;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * A passkey (WebAuthn Level 3 §4, a discoverable credential): the credential record of §6.1.4, with the
 * name the user gave it and its revocation, which keeps the row for the audit trail. Several per user.
 * The provider holds the public key only: the private key never leaves the authenticator, and a passkey
 * has no member the provider could leak.
 */
final class PasskeyCredential extends RevocableCredential implements
    ExternallyIdentifiedCredentialInterface,
    NameableCredentialInterface,
    ProvingCredentialInterface
{
    public const int NAME_MAX_LENGTH = 255;

    /**
     * The one type of WebAuthn Level 3 §5.8.2.
     */
    private const string TYPE = PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY;

    public private(set) string $name;

    public private(set) int $signCount;

    public private(set) bool $backupEligible;

    public private(set) bool $backedUp;

    /**
     * The credential ID (WebAuthn Level 3 §6.3.2), as the authenticator issued it: what a party presents
     * before anything is verified, unique in the tenant (§7.1, step 26), stored under this name whatever the
     * kind that is presented by one.
     */
    public readonly string $externalId;

    /**
     * The credential ID by the name WebAuthn gives it.
     */
    public string $credentialId {
        get => $this->externalId;
    }

    /**
     * @param string $credentialId the credential ID (WebAuthn Level 3 §6.3.2), as the authenticator issued it
     * @param string $publicKey the public key as a COSE_Key (RFC 9052 §7), as the authenticator encoded it
     * @param list<string> $transports the transports the authenticator declared (§5.8.4), hints for the next ceremony
     */
    public function __construct(
        Uuid $id,
        User $user,
        string $name,
        string $credentialId,
        public readonly string $publicKey,
        int $signCount,
        public readonly array $transports,
        bool $backupEligible,
        bool $backedUp,
        public readonly Uuid $aaguid,
        public readonly PasskeyUserHandle $userHandle,
        DateTimeImmutable $createdAt,
    ) {
        parent::__construct($id, $user, $createdAt);
        $this->name = self::normalizeName($name);
        if ($credentialId === '') {
            throw new InvalidArgumentException('A passkey has a credential ID.');
        }
        $this->externalId = $credentialId;
        if ($publicKey === '') {
            throw new InvalidArgumentException('A passkey has a public key.');
        }
        if ($signCount < 0) {
            throw new InvalidArgumentException('A signature counter is never negative (WebAuthn Level 3 §6.1.1).');
        }
        if ($backedUp && ! $backupEligible) {
            throw new InvalidArgumentException('A passkey that is not eligible for backup cannot be backed up (WebAuthn Level 3 §6.1.3).');
        }
        $this->signCount = $signCount;
        $this->backupEligible = $backupEligible;
        $this->backedUp = $backedUp;
    }

    /**
     * From the record the registration ceremony of webauthn-lib returns (WebAuthn Level 3 §7.1, step 26):
     * the backup flags are those of the authenticator data, never assumed.
     */
    public static function fromRecord(Uuid $id, User $user, string $name, CredentialRecord $record, DateTimeImmutable $createdAt): self
    {
        if ($record->backupEligible === null || $record->backupStatus === null) {
            throw new InvalidArgumentException('A passkey is enrolled with the backup flags of its authenticator data (WebAuthn Level 3 §6.1.3).');
        }
        if ($record->type !== self::TYPE) {
            throw new InvalidArgumentException(sprintf('A passkey is a credential of type "%s", not "%s".', self::TYPE, $record->type));
        }

        return new self(
            $id,
            $user,
            $name,
            $record->publicKeyCredentialId,
            $record->credentialPublicKey,
            $record->counter,
            array_values($record->transports),
            $record->backupEligible,
            $record->backupStatus,
            $record->aaguid,
            new PasskeyUserHandle($record->userHandle),
            $createdAt,
        );
    }

    /**
     * The record the authentication ceremony of webauthn-lib verifies an assertion against (§7.2). The
     * attestation is not kept: verified when present at enrolment, never required, no policy reads it later.
     */
    public function record(): CredentialRecord
    {
        return CredentialRecord::create(
            $this->credentialId,
            self::TYPE,
            $this->transports,
            'none',
            EmptyTrustPath::create(),
            $this->aaguid,
            $this->publicKey,
            $this->userHandle->value,
            $this->signCount,
            null,
            $this->backupEligible,
            $this->backedUp,
        );
    }

    public function kind(): string
    {
        return 'passkey';
    }

    /**
     * The name its holder gave it, which is theirs to correct: the same rule as the one it was enrolled
     * under, so a name this object would not have accepted then is not accepted now.
     */
    public function rename(string $name): void
    {
        $this->name = self::normalizeName($name);
    }

    public function label(): string
    {
        return $this->name;
    }

    /**
     * What an assertion of this passkey may prove (architecture §4.3): the key as its backup eligibility
     * says, "swk" for a synced passkey and "hwk" for one bound to its authenticator, and "user", the
     * presence WebAuthn Level 3 §7.2 requires of every assertion (step 16). Not "mfa": the provider asks
     * the authenticator to verify the user when it can (userVerification "preferred") and learns whether
     * it did from the flags of the assertion, so it promises nothing of it ahead. Nothing for a revoked passkey.
     */
    public function provableMethods(): array
    {
        if ($this->isRevoked()) {
            return [];
        }

        return [self::keyMethod($this->backupEligible), new AuthenticationMethod(AuthenticationMethod::USER_PRESENCE)];
    }

    /**
     * What an assertion proves, as "amr" values (RFC 8176 §2), read from the flags of its authenticator
     * data (WebAuthn Level 3 §6.1): "hwk" for a key bound to its authenticator (the BE flag clear, §6.1.3),
     * "swk" for a synced passkey (the flag set: the key lives wherever the provider of the user copies it);
     * "user" when the authenticator tested that a user is present (UP, §6.2.4); "mfa" when it verified the
     * user (UV, §6.2.3): the key was unlocked by a PIN or a biometric the authenticator does not name, the
     * multi-factor cryptographic device of NIST SP 800-63B §5.1.9.
     *
     * @return non-empty-list<AuthenticationMethod>
     */
    public function methods(AuthenticatorData $data): array
    {
        $methods = [self::keyMethod($data->isBackupEligible())];
        if ($data->isUserPresent()) {
            $methods[] = new AuthenticationMethod(AuthenticationMethod::USER_PRESENCE);
        }
        if ($data->isUserVerified()) {
            $methods[] = new AuthenticationMethod(AuthenticationMethod::MULTIPLE_FACTORS);
        }

        return $methods;
    }

    /**
     * The check of webauthn-lib (the CheckCounter step, Counter\ThrowExceptionIfInvalid), made a question:
     * when either count is non-zero, a count that did not grow means a cloned authenticator or a replay
     * (WebAuthn Level 3 §6.1.1); a synced passkey keeps it at zero, so the answer is an event, not a refusal.
     */
    public function signCountRegresses(AuthenticatorData $data): bool
    {
        return ($data->signCount !== 0 || $this->signCount !== 0) && $data->signCount <= $this->signCount;
    }

    /**
     * WebAuthn Level 3 §7.2, step 26: the sign count and the backup state of the assertion replace the stored ones.
     */
    public function recordUse(AuthenticatorData $data, DateTimeImmutable $now): void
    {
        $this->refuseIfRevoked();
        if ($data->isBackedUp() && ! $data->isBackupEligible()) {
            throw new InvalidArgumentException('A passkey that is not eligible for backup cannot be backed up (WebAuthn Level 3 §6.1.3).');
        }
        $this->signCount = $data->signCount;
        $this->backupEligible = $data->isBackupEligible();
        $this->backedUp = $data->isBackedUp();
        $this->markUsed($now);
    }

    /**
     * Where the key lives, as an "amr" value (RFC 8176 §2): "swk" for a synced passkey, the BE flag set
     * (WebAuthn Level 3 §6.1.3), "hwk" for one bound to its authenticator. Read from the stored flag when
     * the question is what this passkey may prove, and from the assertion when it is what one just proved.
     */
    private static function keyMethod(bool $backupEligible): AuthenticationMethod
    {
        return new AuthenticationMethod($backupEligible ? AuthenticationMethod::SOFTWARE_KEY : AuthenticationMethod::HARDWARE_KEY);
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('A passkey name is 1 to %d characters long.', self::NAME_MAX_LENGTH));
        }

        return $name;
    }
}
