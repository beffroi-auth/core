<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Totp;

use Beffroi\Core\Application\Credential\CredentialRevocation;
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
 * The step of the first code is the last accepted one, so that it does not sign the user in a second time.
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
        private CredentialRevocation $revocation,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws CredentialEnrolmentFailedException
     */
    public function begin(User $user): TotpProvisioning
    {
        $this->refuseASecondOne($user);

        return $this->provisioningFor($user);
    }

    /**
     * Decision 0026: a ticket enrols an app in place of the one the person holds.
     *
     * @throws CredentialEnrolmentFailedException
     */
    public function beginInPlaceOfTheOneHeld(User $user): TotpProvisioning
    {
        $this->refuseWhatTheTenantRefuses($user);

        return $this->provisioningFor($user);
    }

    /**
     * @throws CredentialEnrolmentFailedException
     * @throws KmsUnavailableException
     */
    public function complete(User $user, TotpProvisioning $challenge, string $code, RequestContext $context): TotpCredential
    {
        $this->refuseASecondOne($user);

        return $this->enrol($user, $challenge, $code, $context, null);
    }

    /**
     * The TOTP the person holds is revoked once the code matches and the new secret is sealed, never before.
     *
     * @throws CredentialEnrolmentFailedException
     * @throws KmsUnavailableException
     */
    public function completeInPlaceOfTheOneHeld(User $user, TotpProvisioning $challenge, string $code, RequestContext $context): TotpCredential
    {
        $this->refuseWhatTheTenantRefuses($user);

        return $this->enrol($user, $challenge, $code, $context, $this->activeTotp->of($user));
    }

    private function provisioningFor(User $user): TotpProvisioning
    {
        $secret = new TotpSecret($this->randomness->bytes(TotpSecret::LENGTH));

        return new TotpProvisioning($secret, $this->algorithm->provisioningUri($secret, $user->tenant->name, $user->username), $this->backupCodes->draw());
    }

    /**
     * @throws CredentialEnrolmentFailedException
     * @throws KmsUnavailableException
     */
    private function enrol(User $user, TotpProvisioning $challenge, string $code, RequestContext $context, ?TotpCredential $held): TotpCredential
    {
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
        if ($held !== null) {
            $this->revocation->revoke($held, $context);
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
        $this->refuseWhatTheTenantRefuses($user);
        if ($this->activeTotp->of($user) !== null) {
            throw new CredentialEnrolmentFailedException('The user has a TOTP already; it is revoked before another is enrolled.');
        }
    }

    /**
     * @throws CredentialEnrolmentFailedException
     */
    private function refuseWhatTheTenantRefuses(User $user): void
    {
        $this->enrolments->assertTheFloorAdmits($user->tenant, 'one-time code', [TotpCredential::method()]);
        $this->enrolments->assertTheirDomainAdmits($user, 'one-time code');
    }
}
