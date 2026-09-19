<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Passkey;

use Heidi\Core\Application\Credential\CredentialEnrolmentInterface;
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
 *
 * @implements CredentialEnrolmentInterface<PublicKeyCredentialCreationOptions>
 */
final readonly class PasskeyEnrolment implements CredentialEnrolmentInterface
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private PasskeyRegistrarInterface $registrar,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    public function kind(): string
    {
        return PasskeyCredential::class;
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
     * in the tenant is refused (§7.1, step 26).
     */
    public function complete(User $user, string $name, object $challenge, string $answer, RequestContext $context): PasskeyCredential
    {
        if (! $challenge instanceof PublicKeyCredentialCreationOptions) {
            throw new InvalidArgumentException('The challenge of a passkey enrolment is its creation options.');
        }
        try {
            $record = $this->registrar->verifyAttestation($challenge, $answer);
        } catch (PasskeyCeremonyFailedException $exception) {
            throw new CredentialEnrolmentFailedException($exception->getMessage(), 0, $exception);
        }
        $passkeys = $this->credentials->findByUser($user, PasskeyCredential::class);
        if ($passkeys !== [] && ! $passkeys[0]->userHandle->equals(new PasskeyUserHandle($record->userHandle))) {
            throw new CredentialEnrolmentFailedException('The options were not drawn for this user.');
        }
        if ($this->credentials->findByExternalId($user->tenant, PasskeyCredential::class, $record->publicKeyCredentialId) !== null) {
            throw new CredentialEnrolmentFailedException('This passkey is enrolled already.');
        }
        $now = $this->clock->now();
        $credential = PasskeyCredential::fromRecord(Uuid::v7(), $user, $name, $record, $now);
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialEnrolled($credential, $now, $context));

        return $credential;
    }
}
