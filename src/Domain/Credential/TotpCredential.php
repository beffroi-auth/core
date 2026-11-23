<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use function count;
use DateTimeImmutable;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\LogicException;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * RFC 6238
 */
final class TotpCredential extends RevocableCredential implements SecondFactorCredentialInterface, ProvingCredentialInterface
{
    /**
     * RFC 6238 §5.2
     */
    public const int PERIOD = 30;

    public const int DIGITS = 6;

    /**
     * RFC 6238 §5.1
     *
     * SHA-1 is what Google Authenticator and the others implement.
     */
    public const string ALGORITHM = 'sha1';

    /**
     * RFC 6238 §5.2
     */
    public const int DRIFT = 1;

    public const int BACKUP_CODES = 10;

    public private(set) int $lastAcceptedStep;

    /**
     * @var list<BackupCode>
     */
    public private(set) array $backupCodes;

    /**
     * @param list<BackupCode> $backupCodes
     * @param int $lastAcceptedStep the step of the code that completed the enrolment
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

    public static function method(): AuthenticationMethod
    {
        return new AuthenticationMethod(AuthenticationMethod::ONE_TIME_PASSWORD);
    }

    public function provableMethods(): array
    {
        return $this->isRevoked() ? [] : [self::method()];
    }

    /**
     * RFC 6238 §5.2
     */
    public function acceptsStep(int $step): bool
    {
        return $step > $this->lastAcceptedStep;
    }

    public function recordUse(int $step, DateTimeImmutable $now): void
    {
        $this->refuseIfRevoked();
        if (! $this->acceptsStep($step)) {
            throw new LogicException('A TOTP code is accepted once (RFC 6238 §5.2).');
        }
        $this->lastAcceptedStep = $step;
        $this->markUsed($now);
    }

    /**
     * @param list<BackupCode> $backupCodes
     *
     * @throws InvalidArgumentException when they are not as many as a credential holds
     * @throws LogicException when the credential is revoked
     */
    public function renewBackupCodes(array $backupCodes): void
    {
        $this->refuseIfRevoked();
        if (count($backupCodes) !== self::BACKUP_CODES) {
            throw new InvalidArgumentException(sprintf('A TOTP credential holds %d backup codes.', self::BACKUP_CODES));
        }
        $this->backupCodes = $backupCodes;
    }

    public function holdsUnspentBackupCode(int $index): bool
    {
        $code = $this->backupCodes[$index] ?? null;

        return $code !== null && ! $code->isSpent();
    }

    public function spendBackupCode(int $index, DateTimeImmutable $now): void
    {
        $this->refuseIfRevoked();
        $code = $this->backupCodes[$index] ?? throw new InvalidArgumentException(sprintf('There is no backup code at position %d.', $index));
        $this->backupCodes[$index] = $code->spend($now);
        $this->markUsed($now);
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            ...parent::classifiedFields(),
            'sealedSecret' => new NoPersonalDatum(Reason::AN_ENVELOPE),
            'lastAcceptedStep' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'backupCodes' => new NoPersonalDatum(Reason::HASHED),
        ];
    }
}
