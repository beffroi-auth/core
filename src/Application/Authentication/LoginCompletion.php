<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Application\Totp\TotpAuthentication;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\PendingLogin;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KmsUnavailableException;
use SensitiveParameter;

/**
 * The second step of a login (architecture §3.2): the code typed for a login the first step held, verified
 * by TotpAuthentication for the user the login names, and the session opened through the SessionOpener
 * on both what the first step proved and "otp", every method dated now, because this is when the
 * authentication took place in the sense of auth_time. What the held login must still be is
 * PendingLoginVerification's, which the firewall asks the same question of (issue #130); the code itself
 * is refused, counted and announced by TotpAuthentication. The counterpart of PasswordAuthenticator,
 * which holds the login this completes.
 */
final readonly class LoginCompletion
{
    public function __construct(
        private PendingLoginVerification $verification,
        private TotpAuthentication $authentication,
        private SessionOpener $opener,
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
        $user = $this->verification->verify($tenant, $login, $context);
        $method = $this->authentication->authenticate($user, $code, $context);

        return $this->opener->open($tenant, $user, [...$login->methods, $method], $context);
    }
}
