<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use function count;
use DateTimeImmutable;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\LogicException;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * A time-based one-time password (RFC 6238) as a second factor on a password account: the shared secret
 * sealed by the KMS of the tenant (an envelope, never the secret), the last time step whose code was
 * accepted so that a code is accepted once (§5.2), the backup codes, and its revocation, which keeps the
 * row for the audit trail. One active per user; the parameters are those every authenticator implements
 * (§5.1: thirty seconds, six digits, HMAC-SHA-1) and are not a choice. While one is active, the password
 * of the user signs nobody in on its own (SecondFactorCredentialInterface).
 */
final class TotpCredential extends Credential implements SecondFactorCredentialInterface
{
    /**
     * RFC 6238 §5.2: thirty seconds.
     */
    public const int PERIOD = 30;

    public const int DIGITS = 6;

    /**
     * RFC 6238 §5.1: HMAC-SHA-1, what Google Authenticator and the others implement.
     */
    public const string ALGORITHM = 'sha1';

    /**
     * RFC 6238 §5.2: at most one time step of clock drift on each side.
     */
    public const int DRIFT = 1;

    public const int BACKUP_CODES = 10;

    public private(set) int $lastAcceptedStep;

    /**
     * @var list<BackupCode>
     */
    public private(set) array $backupCodes;

    public private(set) ?DateTimeImmutable $revokedAt = null;

    /**
     * @param string $sealedSecret the envelope the SecretSealerInterface wrote, opaque bytes
     * @param list<BackupCode> $backupCodes
     * @param int $lastAcceptedStep the time step of the code that completed the enrolment: a credential exists only once a code was verified
     */
    public function __construct(
        Uuid $id,
        User $user,
        public readonly string $sealedSecret,
        array $backupCodes,
        int $lastAcceptedStep,
        DateTimeImmutable $createdAt,
    ) {
        parent::__construct($id, $user, $createdAt);
        if ($sealedSecret === '') {
            throw new InvalidArgumentException('A TOTP credential holds its secret sealed.');
        }
        if (count($backupCodes) !== self::BACKUP_CODES) {
            throw new InvalidArgumentException(sprintf('A TOTP credential holds %d backup codes.', self::BACKUP_CODES));
        }
        if ($lastAcceptedStep < 0) {
            throw new InvalidArgumentException('A time step is never negative (RFC 6238 §4.2).');
        }
        $this->backupCodes = $backupCodes;
        $this->lastAcceptedStep = $lastAcceptedStep;
    }

    public function kind(): string
    {
        return 'totp';
    }

    /**
     * What a code proves, as an "amr" value (RFC 8176 §2): "otp", a backup code as much as a TOTP.
     */
    public static function method(): AuthenticationMethod
    {
        return new AuthenticationMethod(AuthenticationMethod::ONE_TIME_PASSWORD);
    }

    /**
     * Every method a code may prove: what a provider that asks for codes can announce (decision 0010,
     * architecture §4.8).
     *
     * @return non-empty-list<AuthenticationMethod>
     */
    public static function provableMethods(): array
    {
        return [self::method()];
    }

    /**
     * RFC 6238 §5.2: a code is accepted once, and the accepted step moves forward, never back; the code
     * of an earlier step, whatever the drift, is refused after a later one was accepted.
     */
    public function acceptsStep(int $step): bool
    {
        return $step > $this->lastAcceptedStep;
    }

    public function recordUse(int $step, DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            throw new LogicException('A revoked TOTP credential never authenticates again.');
        }
        if (! $this->acceptsStep($step)) {
            throw new LogicException('A TOTP code is accepted once (RFC 6238 §5.2).');
        }
        $this->lastAcceptedStep = $step;
        $this->markUsed($now);
    }

    /**
     * The code at that position of the list is spent and the credential used.
     */
    public function spendBackupCode(int $index, DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            throw new LogicException('A revoked TOTP credential never authenticates again.');
        }
        $code = $this->backupCodes[$index] ?? throw new InvalidArgumentException(sprintf('There is no backup code at position %d.', $index));
        $this->backupCodes[$index] = $code->spend($now);
        $this->markUsed($now);
    }

    /**
     * The row stays for the audit trail.
     */
    public function revoke(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            return;
        }
        $this->revokedAt = $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }
}
