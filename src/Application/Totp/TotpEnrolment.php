<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use Beffroi\Core\Application\Credential\EnrolmentRefusal;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Credential\TotpProvisioning;
use Beffroi\Core\Domain\Credential\TotpSecret;
use Beffroi\Core\Domain\Event\CredentialEnrolled;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\RandomnessInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Beffroi\Core\Port\SecretSealerInterface;
use Beffroi\Core\Port\TotpAlgorithmInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

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
        private EnrolmentRefusal $enrolments,
        private UuidGeneratorInterface $uuids,
        private RandomnessInterface $randomness,
        private BackupCodeSet $backupCodes,
        private ActiveTotp $activeTotp,
        private TotpAlgorithmInterface $algorithm,
        private TotpCodeMatcher $matcher,
        private SecretSealerInterface $sealer,
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
        $secret = new TotpSecret($this->randomness->bytes(TotpSecret::LENGTH));

        return new TotpProvisioning($secret, $this->algorithm->provisioningUri($secret, $user->tenant->name, $user->username), $this->backupCodes->draw());
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
                $this->uuids->timeOrdered(),
                $user,
                $sealed,
                $this->backupCodes->hashed($challenge->backupCodes),
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
    /**
     * What both steps refuse before anything is drawn or written: a second TOTP, and a TOTP at all where
     * the floor of the tenant admits none (issue #327) or for a person of a federated domain (decision
     * 0029), so that nobody is shown a secret to scan that would prove no login here.
     *
     * @throws CredentialEnrolmentFailedException
     */
    private function refuseASecondOne(User $user): void
    {
        $this->enrolments->assertTheFloorAdmits($user->tenant, 'one-time code', [TotpCredential::method()]);
        $this->enrolments->assertTheirDomainAdmits($user, 'one-time code');
        if ($this->activeTotp->of($user) !== null) {
            throw new CredentialEnrolmentFailedException('The user has a TOTP already; it is revoked before another is enrolled.');
        }
    }
}
