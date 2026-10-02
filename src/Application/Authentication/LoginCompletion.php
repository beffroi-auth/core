<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Application\Totp\TotpAuthentication;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\PendingLogin;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use SensitiveParameter;

/**
 * The second step of a login (architecture §3.2): the code typed for a login the first step held, verified
 * by TotpAuthentication for the user the login names, and the session opened through the SessionOpener
 * on both what the first step proved and "otp", every method dated now, because this is when the
 * authentication took place in the sense of auth_time. A login past its lifetime is refused before the
 * code is read, and so is one that names nobody of this tenant any more; the code itself is refused,
 * counted and announced by TotpAuthentication. The counterpart of PasswordAuthenticator, which holds
 * the login this completes.
 */
final readonly class LoginCompletion
{
    public function __construct(
        private UserRepositoryInterface $users,
        private TotpAuthentication $authentication,
        private SessionOpener $opener,
        private AuthenticationRefusal $refusals,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AuthenticationFailedException
     * @throws KmsUnavailableException when the KMS of the tenant does not open the secret of the code: nothing counted, nothing announced
     */
    public function complete(
        Tenant $tenant,
        PendingLogin $login,
        #[SensitiveParameter]
        string $code,
        RequestContext $context,
    ): AuthenticationSession {
        $user = $this->users->find($login->userId);
        if ($user === null || ! $user->tenant->equals($tenant)) {
            throw $this->refusals->refuse(
                $tenant,
                $context,
                AuthenticationFailureReason::UnknownUser,
                TotpCredential::method(),
                $login->userId->toRfc4122(),
                null,
            );
        }
        if ($login->isExpired($this->clock->now())) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::ExpiredLogin, TotpCredential::method(), $user->username, $user);
        }
        $method = $this->authentication->authenticate($user, $code, $context);

        return $this->opener->open($tenant, $user, [...$login->methods, $method], $context);
    }
}
