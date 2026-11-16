<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Passkey;

use Beffroi\Core\Application\Credential\EnrolmentRefusal;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Credential\PasskeyUserHandle;
use Beffroi\Core\Domain\Event\CredentialEnrolled;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\PasskeyAttestationRefusedException;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\RandomnessInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Beffroi\Core\Port\PasskeyRegistrarInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webauthn\PublicKeyCredentialCreationOptions;

final readonly class PasskeyEnrolment
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private EnrolmentRefusal $enrolments,
        private UuidGeneratorInterface $uuids,
        private RandomnessInterface $randomness,
        private PasskeyRegistrarInterface $registrar,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * WebAuthn Level 3 §14.6.1
     */
    public function begin(User $user): PublicKeyCredentialCreationOptions
    {
        $passkeys = $this->credentials->findByUser($user, PasskeyCredential::class);
        $userHandle = $passkeys === [] ? new PasskeyUserHandle($this->randomness->bytes(PasskeyUserHandle::LENGTH)) : $passkeys[0]->userHandle;

        return $this->registrar->creationOptions($user, $userHandle, $passkeys);
    }

    /**
     * WebAuthn Level 3 §7.1 step 26
     *
     * @param string $answer the attestation response, as the integration received it
     *
     * @throws CredentialEnrolmentFailedException
     * @throws PasskeyAttestationRefusedException
     */
    public function complete(
        User $user,
        string $name,
        PublicKeyCredentialCreationOptions $challenge,
        string $answer,
        RequestContext $context,
    ): PasskeyCredential {
        try {
            $registration = $this->registrar->verifyAttestation($user->tenant, $challenge, $answer);
        } catch (PasskeyCeremonyFailedException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), previous: $exception);
        }
        $record = $registration->record;
        $passkeys = $this->credentials->findByUser($user, PasskeyCredential::class);
        try {
            $handle = new PasskeyUserHandle($record->userHandle);
        } catch (InvalidArgumentException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), previous: $exception);
        }
        if ($passkeys !== [] && ! $passkeys[0]->userHandle->equals($handle)) {
            throw new CredentialEnrolmentFailedException('The options were not drawn for this user.');
        }
        if ($this->credentials->findByExternalId($user->tenant, PasskeyCredential::class, $record->publicKeyCredentialId) !== null) {
            throw new CredentialEnrolmentFailedException('This passkey is enrolled already.');
        }
        $now = $this->clock->now();
        try {
            $credential = PasskeyCredential::fromRecord($this->uuids->timeOrdered(), $user, $name, $registration, $now);
        } catch (InvalidArgumentException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), previous: $exception);
        }
        // After the ceremony: only its flags tell a synced passkey ("swk") from a hardware-bound one ("hwk").
        $this->enrolments->assertTheFloorAdmits($user->tenant, 'passkey', $credential->provableMethods());
        $this->enrolments->assertTheirDomainAdmits($user, 'passkey');
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialEnrolled($credential, $now, $context));

        return $credential;
    }
}
