<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Passkey;

use function array_filter;
use function array_values;
use Heidi\Core\Application\Authentication\AuthenticationFailedException;
use Heidi\Core\Application\Authentication\SessionOpener;
use Heidi\Core\Domain\Credential\PasskeyCredential;
use Heidi\Core\Domain\Event\AuthenticationFailed;
use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Domain\Event\PasskeyUsed;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
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
        private PasskeyAuthenticatorInterface $authenticator,
        private SessionOpener $opener,
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
        $allowed = $user === null ? [] : array_values(array_filter(
            $this->credentials->findByUser($user, PasskeyCredential::class),
            static fn (PasskeyCredential $passkey): bool => ! $passkey->isRevoked(),
        ));

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
            throw $this->failure($tenant, $context, null, AuthenticationFailureReason::InvalidCredential, null);
        }
        $credential = $assertion->credential;
        $methods = $credential->methods($assertion->authenticatorData);
        if (! $credential->tenant->equals($tenant)) {
            throw $this->failure($tenant, $context, $methods[0], AuthenticationFailureReason::InvalidCredential, null);
        }
        if ($credential->isRevoked()) {
            throw $this->failure($tenant, $context, $methods[0], AuthenticationFailureReason::RevokedCredential, $credential->user);
        }
        if (! $credential->user->isEnabled()) {
            throw $this->failure($tenant, $context, $methods[0], AuthenticationFailureReason::DisabledUser, $credential->user);
        }

        $now = $this->clock->now();
        $signCountRegressed = $credential->signCountRegresses($assertion->authenticatorData);
        $credential->recordUse($assertion->authenticatorData, $now);
        $this->credentials->save($credential);
        $this->events->dispatch(new PasskeyUsed($credential, $now, $context, $signCountRegressed));

        return $this->opener->open($tenant, $credential->user, $methods, $context);
    }

    private function failure(
        Tenant $tenant,
        RequestContext $context,
        ?AuthenticationMethod $method,
        AuthenticationFailureReason $reason,
        ?User $user,
    ): AuthenticationFailedException {
        $this->events->dispatch(new AuthenticationFailed(
            $this->clock->now(),
            $tenant,
            $context,
            $method,
            $reason,
            null,
            $user,
        ));

        return new AuthenticationFailedException($reason);
    }
}
