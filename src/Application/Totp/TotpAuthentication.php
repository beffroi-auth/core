<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Totp;

use Heidi\Core\Application\Authentication\AuthenticationFailedException;
use Heidi\Core\Domain\Credential\BackupCode;
use Heidi\Core\Domain\Credential\TotpCredential;
use Heidi\Core\Domain\Credential\TotpSecret;
use Heidi\Core\Domain\Event\AuthenticationFailed;
use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Domain\Event\TotpUsed;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Domain\Session\RequestContext;
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
 * six digits are guessable. Every outcome is an event carrying the request context.
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
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * A disabled user is refused after the code was checked, so that a refusal costs the same as a wrong code.
     *
     * @throws AuthenticationFailedException
     */
    public function authenticate(User $user, #[SensitiveParameter] string $code, RequestContext $context): AuthenticationMethod
    {
        if (! $this->attempts->attempt($user)) {
            throw $this->failure($user, $context, AuthenticationFailureReason::TooManyAttempts);
        }
        $credential = $this->activeTotp->of($user) ?? throw $this->failure($user, $context, AuthenticationFailureReason::NoSuchCredential);
        $now = $this->clock->now();

        if (TotpCodeMatcher::looksLikeCode($code)) {
            $step = $this->matcher->matchingStep(new TotpSecret($this->sealer->unseal($user->tenant, $credential->sealedSecret)), $code, $now);
            if ($step === null) {
                throw $this->failure($user, $context, AuthenticationFailureReason::InvalidCredential);
            }
            if (! $credential->acceptsStep($step)) {
                throw $this->failure($user, $context, AuthenticationFailureReason::ReplayedCode);
            }
            $backupCode = false;
            $record = static fn () => $credential->recordUse($step, $now);
        } else {
            $index = $this->matchingBackupCode($credential, $code) ?? throw $this->failure($user, $context, AuthenticationFailureReason::InvalidCredential);
            $backupCode = true;
            $record = static fn () => $credential->spendBackupCode($index, $now);
        }
        if (! $user->isEnabled()) {
            throw $this->failure($user, $context, AuthenticationFailureReason::DisabledUser);
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

    private function failure(User $user, RequestContext $context, AuthenticationFailureReason $reason): AuthenticationFailedException
    {
        $this->events->dispatch(new AuthenticationFailed(
            $this->clock->now(),
            $user->tenant,
            $context,
            TotpCredential::method(),
            $reason,
            $user->username,
            $user,
        ));

        return new AuthenticationFailedException($reason);
    }
}
