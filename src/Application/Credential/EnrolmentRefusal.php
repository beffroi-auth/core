<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use function implode;
use function array_map;
use function sprintf;

/**
 * Refuses one enrolment the floor of the tenant does not admit (issue #327), wherever a credential is
 * posed: the password of a provisioning or of a change, a TOTP secret, a passkey. The counterpart of
 * AuthenticationRefusal, which refuses the login; a tenant that signs its users in by passkey does not
 * merely refuse the password at the door, it never holds one.
 *
 * **The question is asked of what the credential proves and never of its kind** (decision 0006, the
 * SOLID rule of the repository): the floor reads methods (AuthenticationPredicate::admits()), each kind
 * says which ones it proves (ProvingCredentialInterface::provableMethods()), and the kind is carried here
 * only to say so in the refusal. A kind added to this provider is admitted or refused by what it proves,
 * with nothing to edit here.
 */
final readonly class EnrolmentRefusal
{
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
            $tenant->loginFloor->value,
            implode(', ', array_map(static fn (AuthenticationMethod $method): string => $method->value, $methods)),
        ));
    }
}
