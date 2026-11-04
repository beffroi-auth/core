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
