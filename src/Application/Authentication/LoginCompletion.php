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
 * Dates every method now, the password of the first step included: auth_time is when the login completes.
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
     * @throws KmsUnavailableException with nothing counted or announced
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
