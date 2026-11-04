<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
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
 * WebAuthn Level 3 §4, §6.1.4
 */
final class PasskeyCredential extends RevocableCredential implements
    ExternallyIdentifiedCredentialInterface,
    ModelDeclaringCredentialInterface,
    NameableCredentialInterface,
    ProvingCredentialInterface
{
    public const int NAME_MAX_LENGTH = 255;

    private const string TYPE = PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY;

    public private(set) string $name;

    public private(set) int $signCount;

    public readonly bool $backupEligible;

    public private(set) bool $backedUp;

    public readonly string $externalId;

    public string $credentialId {
        get => $this->externalId;
    }

    /**
     * @var list<AuthenticatorTransport>
     */
    public array $reachedThrough {
        get => AuthenticatorTransport::definedIn($this->transports);
    }

    /**
     * @param string $credentialId as the authenticator issued it
     * @param string $publicKey a COSE_Key (RFC 9052 §7)
     * @param list<string> $transports as declared, unknown values kept (WebAuthn Level 3 §5.2.1)
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
     * WebAuthn Level 3 §7.1, step 26
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
     * The attestation is not kept, hence "none": verified at enrolment when present, nothing reads it later.
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

    public function rename(string $name): void
    {
        $this->name = self::normalizeName($name);
    }

    public function label(): string
    {
        return $this->name;
    }

    /**
     * Not "mfa": user verification is only preferred, so it is read from an assertion and never promised ahead.
     */
    public function provableMethods(): array
    {
        if ($this->isRevoked()) {
            return [];
        }

        return [$this->keyMethod(), new AuthenticationMethod(AuthenticationMethod::USER_PRESENCE)];
    }

    /**
     * RFC 8176 §2, WebAuthn Level 3 §6.1, NIST SP 800-63B §3.1.7.1
     *
     * @return non-empty-list<AuthenticationMethod>
     */
    public function methods(AuthenticatorData $data): array
    {
        $methods = [$this->keyMethod()];
        if ($data->isUserPresent()) {
            $methods[] = new AuthenticationMethod(AuthenticationMethod::USER_PRESENCE);
        }
        if ($data->isUserVerified()) {
            $methods[] = new AuthenticationMethod(AuthenticationMethod::MULTIPLE_FACTORS);
        }

        return $methods;
    }

    /**
     * WebAuthn Level 3 §6.1.1
     */
    public function signCountRegresses(AuthenticatorData $data): bool
    {
        return ($data->signCount !== 0 || $this->signCount !== 0) && $data->signCount <= $this->signCount;
    }

    /**
     * WebAuthn Level 3 §6.1.3
     */
    public function backupEligibilityChanged(AuthenticatorData $data): bool
    {
        return $data->isBackupEligible() !== $this->backupEligible;
    }

    /**
     * WebAuthn Level 3 §7.2, step 26
     */
    public function recordUse(AuthenticatorData $data, DateTimeImmutable $now): void
    {
        $this->refuseIfRevoked();
        if ($this->backupEligibilityChanged($data)) {
            throw new InvalidArgumentException('The backup eligibility of a passkey is the one it was enrolled with and never changes (WebAuthn Level 3 §6.1.3).');
        }
        if ($data->isBackedUp() && ! $data->isBackupEligible()) {
            throw new InvalidArgumentException('A passkey that is not eligible for backup cannot be backed up (WebAuthn Level 3 §6.1.3).');
        }
        $this->signCount = $data->signCount;
        $this->backedUp = $data->isBackedUp();
        $this->markUsed($now);
    }

    private function keyMethod(): AuthenticationMethod
    {
        return new AuthenticationMethod($this->backupEligible ? AuthenticationMethod::SOFTWARE_KEY : AuthenticationMethod::HARDWARE_KEY);
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('A passkey name is 1 to %d characters long.', self::NAME_MAX_LENGTH));
        }

        return $name;
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            ...parent::classifiedFields(),
            'name' => new Sealed('passkey_credential'),
            'externalId' => new NoPersonalDatum(Reason::PUBLIC_BY_CONSTRUCTION),
            'publicKey' => new NoPersonalDatum(Reason::PUBLIC_BY_CONSTRUCTION),
            'signCount' => new NoPersonalDatum(Reason::PUBLIC_BY_CONSTRUCTION),
            'transports' => new NoPersonalDatum(Reason::PUBLIC_BY_CONSTRUCTION),
            'backupEligible' => new NoPersonalDatum(Reason::PUBLIC_BY_CONSTRUCTION),
            'backedUp' => new NoPersonalDatum(Reason::PUBLIC_BY_CONSTRUCTION),
            'aaguid' => new NoPersonalDatum(Reason::PUBLIC_BY_CONSTRUCTION),
            'userHandle' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
