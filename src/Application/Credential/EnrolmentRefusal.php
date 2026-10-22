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

/**
 * Refuses one enrolment the floor of the tenant does not admit (issue #327), or that would pose a local
 * credential for a person of a federated domain (decision 0029), wherever a credential is posed: the
 * password of a provisioning or of a change, a TOTP secret, a passkey. The counterpart of
 * AuthenticationRefusal, which refuses the login; a tenant that signs its users in by passkey does not
 * merely refuse the password at the door, it never holds one.
 *
 * **The question is asked of what the credential proves and never of its kind** (decision 0006, the
 * SOLID rule of the repository): the floor reads methods (AuthenticationPredicateInterface::admits()), each kind
 * says which ones it proves (ProvingCredentialInterface::provableMethods()), and the kind is carried here
 * only to say so in the refusal. A kind added to this provider is admitted or refused by what it proves,
 * with nothing to edit here.
 */
final readonly class EnrolmentRefusal
{
    public function __construct(
        private FederatedDomains $federated,
    ) {
    }

    /**
     * @param list<AuthenticationMethod> $methods what one authentication with the credential about to be
     *                                             posed may prove; a credential that could prove nothing
     *                                             is admitted by no floor, the single factor included
     *
     * @throws CredentialEnrolmentFailedException when the floor of the tenant admits none of them
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
     * Decision 0029, beffroi/saml S-63: a person of an e-mail domain the tenant federates to an upstream provider
     * signs in through that provider and through nothing else, so no local credential is posed for them, whoever poses
     * it: the person on the pages of their account, an enrolment ticket, an operator. Asked after the floor, which says
     * nothing of anybody.
     *
     * @param string $kind what is about to be posed, for the refusal to say
     *
     * @throws CredentialEnrolmentFailedException when the person is of a federated domain
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
