<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Totp;

use Heidi\Core\Application\Authentication\AuthenticationFailedException;
use Heidi\Core\Application\Authentication\AuthenticationRefusal;
use Heidi\Core\Domain\Credential\BackupCode;
use Heidi\Core\Domain\Credential\TotpCredential;
use Heidi\Core\Domain\Credential\TotpSecret;
use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Domain\Event\TotpUsed;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Exception\KmsUnavailableException;
use Heidi\Core\Port\CodeAttemptLimiterInterface;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasswordHasherInterface;
use Heidi\Core\Port\SecretSealerInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;

/**
 * Verifies a code for a user who already named themself (a second factor comes after a first one): the
 * code of the authenticator, accepted once within one step of drift (RFC 6238 §5.2), or one of the backup
 * codes, spent once. What it proves is "otp" either way; what the caller does with the proof (open the
 * session, add it to one) is the caller's. The attempts are counted before anything is verified, because
 * six digits are guessable: the limit is read before the secret is opened, so that no code reaches the
 * KMS past it, and the attempt is counted once the secret is open, so that a KMS that does not open it
 * costs the user nothing. Every outcome of the user is an event carrying the request context; a failure
 * of the KMS is not an outcome of the user and goes on as it came.
 */
final readonly class TotpAuthentication
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ActiveTotp $activeTotp,
        private TotpCodeMatcher $matcher,
        private SecretSealerInterface $sealer,
        private PasswordHasherInterface $hasher,
        private CodeAttemptLimiterInterface $attempts,
        private AuthenticationRefusal $refusals,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * A disabled user is refused after the code was checked, so that a refusal costs the same as a wrong code.
     *
     * @throws AuthenticationFailedException
     * @throws KmsUnavailableException when the KMS of the tenant does not open the secret: nothing counted, nothing announced
     */
    public function authenticate(User $user, #[SensitiveParameter] string $code, RequestContext $context): AuthenticationMethod
    {
        if (! $this->attempts->mayAttempt($user)) {
            throw $this->refuse($user, $context, AuthenticationFailureReason::TooManyAttempts);
        }
        $credential = $this->activeTotp->of($user) ?? throw $this->refuse($user, $context, AuthenticationFailureReason::NoSuchCredential);
        $secret = TotpCodeMatcher::looksLikeCode($code) ? new TotpSecret($this->sealer->unseal($user->tenant, $credential->sealedSecret)) : null;
        if (! $this->attempts->attempt($user)) {
            throw $this->refuse($user, $context, AuthenticationFailureReason::TooManyAttempts);
        }
        $now = $this->clock->now();

        if ($secret !== null) {
            $step = $this->matcher->matchingStep($secret, $code, $now);
            if ($step === null) {
                throw $this->refuse($user, $context, AuthenticationFailureReason::InvalidCredential);
            }
            if (! $credential->acceptsStep($step)) {
                throw $this->refuse($user, $context, AuthenticationFailureReason::ReplayedCode);
            }
            $backupCode = false;
            $record = static fn () => $credential->recordUse($step, $now);
        } else {
            $index = $this->matchingBackupCode($credential, $code) ?? throw $this->refuse($user, $context, AuthenticationFailureReason::InvalidCredential);
            $backupCode = true;
            $record = static fn () => $credential->spendBackupCode($index, $now);
        }
        if (! $user->isEnabled()) {
            throw $this->refuse($user, $context, AuthenticationFailureReason::DisabledUser);
        }
        $record();
        $this->credentials->save($credential);
        $this->events->dispatch(new TotpUsed($credential, $now, $context, $backupCode));

        return TotpCredential::method();
    }

    /**
     * The position of the unspent backup code that was typed; every unspent one is verified, so that the
     * time a refusal takes does not say how many are left.
     */
    private function matchingBackupCode(TotpCredential $credential, #[SensitiveParameter] string $typed): ?int
    {
        $typed = BackupCode::normalize($typed);
        $matched = null;
        foreach ($credential->backupCodes as $index => $backupCode) {
            if (! $backupCode->isSpent() && $this->hasher->verify($backupCode->hash, $typed) && $matched === null) {
                $matched = $index;
            }
        }

        return $matched;
    }

    /**
     * Every refusal here is the same statement, whatever the reason: this user, in their tenant, having
     * claimed an "otp". What it announces and what it hands back to throw is the AuthenticationRefusal's,
     * as for every other login.
     */
    private function refuse(User $user, RequestContext $context, AuthenticationFailureReason $reason): AuthenticationFailedException
    {
        return $this->refusals->refuse($user->tenant, $context, $reason, TotpCredential::method(), $user->username, $user);
    }
}
