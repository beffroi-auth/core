<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Passkey;

use Heidi\Core\Domain\Credential\PasskeyCredential;
use Heidi\Core\Domain\Credential\PasskeyUserHandle;
use Heidi\Core\Domain\Event\CredentialEnrolled;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Exception\CredentialEnrolmentFailedException;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\PasskeyCeremonyFailedException;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasskeyRegistrarInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;
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
        $userHandle = $passkeys === [] ? PasskeyUserHandle::generate() : $passkeys[0]->userHandle;

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
    public function complete(User $user, string $name, PublicKeyCredentialCreationOptions $challenge, string $answer, RequestContext $context): PasskeyCredential
    {
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
            $credential = PasskeyCredential::fromRecord(Uuid::v7(), $user, $name, $record, $now);
        } catch (InvalidArgumentException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), previous: $exception);
        }
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialEnrolled($credential, $now, $context));

        return $credential;
    }
}
