<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Passkey;

use Beffroi\Core\Application\Authentication\AuthenticationFailedException;
use Beffroi\Core\Application\Authentication\AuthenticationRefusal;
use Beffroi\Core\Application\Credential\ActiveCredentials;
use Beffroi\Core\Application\Identity\FederatedDomains;
use Beffroi\Core\Application\Authentication\SessionOpener;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Event\PasskeyUsed;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\PasskeyAuthenticatorInterface;
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
        private FederatedDomains $federated,
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
     * An assertion whose backup eligibility is not the one of the enrolment or whose sign count did not grow
     * (decision 0034), a revoked passkey, a disabled user and a person of a federated domain (decision 0029)
     * are refused after the signature was checked, so that a refusal costs the same as a wrong signature.
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
        if ($credential->backupEligibilityChanged($assertion->authenticatorData) || $credential->signCountRegresses($assertion->authenticatorData)) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::InvalidCredential, $methods[0], null, $credential->user);
        }
        if ($credential->isRevoked()) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::RevokedCredential, $methods[0], null, $credential->user);
        }
        if (! $credential->user->isEnabled()) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::DisabledUser, $methods[0], null, $credential->user);
        }
        if (! $this->federated->admitsLocalCredentials($credential->user)) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::FederatedDomain, $methods[0], null, $credential->user);
        }

        $now = $this->clock->now();
        $credential->recordUse($assertion->authenticatorData, $now);
        $this->credentials->save($credential);
        $this->events->dispatch(new PasskeyUsed($credential, $now, $context));

        return $this->opener->open($tenant, $credential->user, $methods, $context);
    }
}
