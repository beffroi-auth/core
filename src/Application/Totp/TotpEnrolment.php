<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Totp;

use function array_map;
use Heidi\Core\Domain\Credential\BackupCode;
use Heidi\Core\Domain\Credential\TotpCredential;
use Heidi\Core\Domain\Credential\TotpProvisioning;
use Heidi\Core\Domain\Credential\TotpSecret;
use Heidi\Core\Domain\Event\CredentialEnrolled;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Exception\CredentialEnrolmentFailedException;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\KmsUnavailableException;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasswordHasherInterface;
use Heidi\Core\Port\SecretSealerInterface;
use Heidi\Core\Port\TotpAlgorithmInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * A TOTP enters the identity model in two steps, from a session the user already holds: the provisioning
 * is the challenge (the secret, the URI the authenticator scans, the backup codes shown once), a first code
 * the answer, and the credential exists only once it holds (RFC 6238 §5.2, the same window as a login);
 * abandoned before, nothing is left. The secret is sealed by the KMS of the tenant before it is stored,
 * the backup codes hashed like passwords; the step of the first code is the last accepted one, so that it
 * does not sign the user in a second time. One active TOTP per user, and it carries no name: nothing
 * distinguishes one authenticator app from another.
 */
final readonly class TotpEnrolment
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ActiveTotp $activeTotp,
        private TotpAlgorithmInterface $algorithm,
        private TotpCodeMatcher $matcher,
        private SecretSealerInterface $sealer,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * The issuer of the URI is the name of the tenant, the account the username, as the Key Uri Format
     * shows them in the authenticator. Nothing is stored here: an enrolment abandoned after this step
     * leaves nothing behind.
     *
     * @throws CredentialEnrolmentFailedException when the user holds an active TOTP already
     */
    public function begin(User $user): TotpProvisioning
    {
        $this->refuseASecondOne($user);
        $secret = TotpSecret::generate();
        $backupCodes = [];
        for ($i = 0; $i < TotpCredential::BACKUP_CODES; ++$i) {
            $backupCodes[] = BackupCode::generate();
        }

        return new TotpProvisioning($secret, $this->algorithm->provisioningUri($secret, $user->tenant->name, $user->username), $backupCodes);
    }

    /**
     * @param TotpProvisioning $challenge what begin() issued for this user
     * @param string $code the first code of the authenticator, as the integration received it
     *
     * @throws CredentialEnrolmentFailedException
     * @throws KmsUnavailableException when the KMS of the tenant does not answer; nothing is written
     */
    public function complete(User $user, TotpProvisioning $challenge, string $code, RequestContext $context): TotpCredential
    {
        $this->refuseASecondOne($user);
        $now = $this->clock->now();
        $step = $this->matcher->matchingStep($challenge->secret, $code, $now);
        if ($step === null) {
            throw new CredentialEnrolmentFailedException('The code does not match the secret.');
        }
        $sealed = $this->sealer->seal($user->tenant, $challenge->secret->bytes);
        try {
            $credential = new TotpCredential(
                Uuid::v7(),
                $user,
                $sealed,
                array_map(fn (string $code): BackupCode => new BackupCode($this->hasher->hash(BackupCode::normalize($code))), $challenge->backupCodes),
                $step,
                $now,
            );
        } catch (InvalidArgumentException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), previous: $exception);
        }
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialEnrolled($credential, $now, $context));

        return $credential;
    }

    /**
     * @throws CredentialEnrolmentFailedException
     */
    private function refuseASecondOne(User $user): void
    {
        if ($this->activeTotp->of($user) !== null) {
            throw new CredentialEnrolmentFailedException('The user has a TOTP already; it is revoked before another is enrolled.');
        }
    }
}
