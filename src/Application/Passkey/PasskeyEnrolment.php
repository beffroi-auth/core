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
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\RandomnessInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use Beffroi\Core\Port\PasskeyRegistrarInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * A passkey enters the identity model, enrolled from a session the user already holds: the creation
 * options are the challenge, the attestation response the answer (WebAuthn Level 3 §7.1, the ceremony
 * behind PasskeyRegistrarInterface). The outcome is an event carrying the request context.
 */
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
     * The user handle is the one the passkeys of the user already carry, revoked or not, or a fresh one
     * for a first passkey (§14.6.1); every passkey of the user, revoked or not, is excluded from the ceremony.
     */
    public function begin(User $user): PublicKeyCredentialCreationOptions
    {
        $passkeys = $this->credentials->findByUser($user, PasskeyCredential::class);
        $userHandle = $passkeys === [] ? new PasskeyUserHandle($this->randomness->bytes(PasskeyUserHandle::LENGTH)) : $passkeys[0]->userHandle;

        return $this->registrar->creationOptions($user, $userHandle, $passkeys);
    }

    /**
     * The record comes out with the handle the options carried; a user keeps one handle across their
     * passkeys, so options drawn for another user are refused with the record, and a credential ID known
     * in the tenant is refused (§7.1, step 26). What the authenticator sent and the model refuses (a
     * handle of another length, missing backup flags, a credential of another type) is a refused
     * enrolment like any other: what this use case throws is what its contract names.
     *
     * @param PublicKeyCredentialCreationOptions $challenge what begin() issued for this user
     * @param string $answer the attestation response, as the integration received it
     *
     * @throws CredentialEnrolmentFailedException
     */
    public function complete(
        User $user,
        string $name,
        PublicKeyCredentialCreationOptions $challenge,
        string $answer,
        RequestContext $context,
    ): PasskeyCredential {
        try {
            $record = $this->registrar->verifyAttestation($user->tenant, $challenge, $answer);
        } catch (PasskeyCeremonyFailedException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), previous: $exception);
        }
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
            $credential = PasskeyCredential::fromRecord($this->uuids->timeOrdered(), $user, $name, $record, $now);
        } catch (InvalidArgumentException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), previous: $exception);
        }
        // the floor is asked here and not of the kind (issue #327): what a passkey proves is read from the
        // flags of its attestation, "swk" for a synced key and "hwk" for one bound to its authenticator,
        // so a tenant that requires a key held in hardware refuses a synced one and only knows which it
        // was once the ceremony answered
        $this->enrolments->assertTheFloorAdmits($user->tenant, 'passkey', $credential->provableMethods());
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialEnrolled($credential, $now, $context));

        return $credential;
    }
}
