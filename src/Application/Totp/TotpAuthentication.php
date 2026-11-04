<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use Beffroi\Core\Application\Authentication\AuthenticationFailedException;
use Beffroi\Core\Application\Authentication\AuthenticationRefusal;
use Beffroi\Core\Domain\Credential\BackupCode;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Credential\TotpSecret;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Event\BackupCodeUsed;
use Beffroi\Core\Domain\Event\TotpUsed;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Port\CodeAttemptLimiterInterface;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\SecretSealerInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;

/**
 * RFC 6238 §5.2
 *
 * The limit of attempts is read before the secret is opened, so that no code reaches the KMS past it, and an
 * attempt is counted once the secret is open, so that a KMS that does not open it costs the user nothing.
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
     * @throws KmsUnavailableException
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
            $record = static fn () => $credential->recordUse($step, $now);
            $announce = static fn (): TotpUsed => new TotpUsed($credential, $now, $context);
        } else {
            $index = $this->matchingBackupCode($credential, $code) ?? throw $this->refuse($user, $context, AuthenticationFailureReason::InvalidCredential);
            $record = static fn () => $credential->spendBackupCode($index, $now);
            $announce = static fn (): BackupCodeUsed => new BackupCodeUsed($credential, $now, $context);
        }
        if (! $user->isEnabled()) {
            throw $this->refuse($user, $context, AuthenticationFailureReason::DisabledUser);
        }
        $record();
        $this->credentials->save($credential);
        $this->events->dispatch($announce());

        return TotpCredential::method();
    }

    /**
     * Every unspent code is verified, so that the time a refusal takes does not say how many are left.
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

    private function refuse(User $user, RequestContext $context, AuthenticationFailureReason $reason): AuthenticationFailedException
    {
        return $this->refusals->refuse($user->tenant, $context, $reason, TotpCredential::method(), $user->username, $user);
    }
}
