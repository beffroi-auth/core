<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\PendingLogin;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Psr\Clock\ClockInterface;
use SensitiveParameter;

/**
 * Signs a user in with a password: what a password proves is PasswordVerification's, and what a login does
 * with the proof is here. A password alone signs nobody in who holds an active second factor
 * (SecondFactorRequirement, architecture §3.2): the login is then held as a PendingLogin, which
 * LoginCompletion turns into a session once the code passes; otherwise the session is opened here through
 * the SessionOpener.
 *
 * It is the counterpart of LoginCompletion for the first step, and the caller of the core: a login on the
 * firewall of the application plays the same verification and holds or opens with the objects of the firewall
 * (issue #130), so the order of a login is written once and the two paths cannot drift.
 */
final readonly class PasswordAuthenticator
{
    public function __construct(
        private PasswordVerification $verification,
        private SessionOpener $opener,
        private SecondFactorRequirement $secondFactor,
        private ClockInterface $clock,
    ) {
    }

    /**
     * The session opened, or the login held until a code.
     *
     * @throws AuthenticationFailedException
     */
    public function authenticate(
        Tenant $tenant,
        string $identifier,
        #[SensitiveParameter]
        string $password,
        RequestContext $context,
    ): AuthenticationSession|PendingLogin {
        $user = $this->verification->verify($tenant, $identifier, $password, $context)->user;
        if ($this->secondFactor->requiredFor($user)) {
            return PendingLogin::provenAt($user->id, [AuthenticationMethod::password()], $this->clock->now());
        }

        return $this->opener->open($tenant, $user, [AuthenticationMethod::password()], $context);
    }
}
