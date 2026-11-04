<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Application\Identity\FederatedDomains;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use function implode;
use function array_map;
use function sprintf;

final readonly class EnrolmentRefusal
{
    public function __construct(
        private FederatedDomains $federated,
    ) {
    }

    /**
     * @param list<AuthenticationMethod> $methods
     *
     * @throws CredentialEnrolmentFailedException
     */
    public function assertTheFloorAdmits(Tenant $tenant, string $kind, array $methods): void
    {
        if ($tenant->loginFloor->admits($methods)) {
            return;
        }

        throw new CredentialEnrolmentFailedException(sprintf(
            'A %s proves no login of the tenant "%s", whose logins prove "%s" at the least: it is not enrolled here. What it would prove is %s.',
            $kind,
            $tenant->slug,
            $tenant->loginFloor->name(),
            implode(', ', array_map(static fn (AuthenticationMethod $method): string => $method->value, $methods)),
        ));
    }

    /**
     * beffroi/saml S-63
     *
     * @throws CredentialEnrolmentFailedException
     */
    public function assertTheirDomainAdmits(User $user, string $kind): void
    {
        if ($this->federated->admitsLocalCredentials($user)) {
            return;
        }

        throw new CredentialEnrolmentFailedException(sprintf(
            'A %s is posed for nobody of an e-mail domain the tenant "%s" federates to an upstream provider, through which alone they sign in.',
            $kind,
            $user->tenant->slug,
        ));
    }
}
