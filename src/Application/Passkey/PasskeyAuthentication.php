<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Passkey;

use Heidi\Core\Application\Authentication\AuthenticationFailedException;
use Heidi\Core\Application\Authentication\AuthenticationRefusal;
use Heidi\Core\Application\Credential\ActiveCredentials;
use Heidi\Core\Application\Authentication\SessionOpener;
use Heidi\Core\Domain\Credential\PasskeyCredential;
use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Domain\Event\PasskeyUsed;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\PasskeyCeremonyFailedException;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasskeyAuthenticatorInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Authenticates a user with a passkey and opens a session, through the SessionOpener, on what the assertion
 * proved (the ceremony behind PasskeyAuthenticatorInterface, WebAuthn Level 3 §7.2). Every outcome is an
 * event carrying the request context, as with a password.
 */
final readonly class PasskeyAuthentication
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ActiveCredentials $active,
        private PasskeyAuthenticatorInterface $authenticator,
        private SessionOpener $opener,
        private AuthenticationRefusal $refusals,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * The passkeys a user who named themself can still sign in with; none when the authenticator is to
     * pick a discoverable credential.
     */
    public function requestOptions(Tenant $tenant, ?User $user = null): PublicKeyCredentialRequestOptions
    {
        $allowed = $user === null ? [] : $this->active->of($user, PasskeyCredential::class);

        return $this->authenticator->requestOptions($tenant, $allowed);
    }

    /**
     * A revoked passkey and a disabled user are refused after the signature was checked, so that a refusal
     * costs the same as a wrong signature; a sign count that did not grow is told, not refused.
     *
     * @throws AuthenticationFailedException
     */
    public function authenticate(Tenant $tenant, PublicKeyCredentialRequestOptions $options, string $response, RequestContext $context): AuthenticationSession
    {
        try {
            $assertion = $this->authenticator->verifyAssertion($tenant, $options, $response);
        } catch (PasskeyCeremonyFailedException) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::InvalidCredential, null, null, null);
        }
        $credential = $assertion->credential;
        $methods = $credential->methods($assertion->authenticatorData);
        if (! $credential->tenant->equals($tenant)) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::InvalidCredential, $methods[0], null, null);
        }
        if ($credential->isRevoked()) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::RevokedCredential, $methods[0], null, $credential->user);
        }
        if (! $credential->user->isEnabled()) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::DisabledUser, $methods[0], null, $credential->user);
        }

        $now = $this->clock->now();
        $signCountRegressed = $credential->signCountRegresses($assertion->authenticatorData);
        $credential->recordUse($assertion->authenticatorData, $now);
        $this->credentials->save($credential);
        $this->events->dispatch(new PasskeyUsed($credential, $now, $context, $signCountRegressed));

        return $this->opener->open($tenant, $credential->user, $methods, $context);
    }
}
