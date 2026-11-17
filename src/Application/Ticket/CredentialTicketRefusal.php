<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Application\Credential\EnrolmentRefusal;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Exception\TicketRefusedException;
use function sprintf;

/**
 * A ticket that poses a credential is refused where the enrolment of that credential would be (decision 0026).
 */
final readonly class CredentialTicketRefusal
{
    public function __construct(
        private EnrolmentRefusal $enrolments,
    ) {
    }

    /**
     * @param list<AuthenticationMethod> $methods what the credential the ticket poses proves
     *
     * @throws TicketRefusedException when the floor of the tenant or the domain of the person admits no such credential
     */
    public function assertItWouldOpen(User $user, string $kind, array $methods): void
    {
        try {
            $this->enrolments->assertTheFloorAdmits($user->tenant, $kind, $methods);
            $this->enrolments->assertTheirDomainAdmits($user, $kind);
        } catch (CredentialEnrolmentFailedException $exception) {
            throw new TicketRefusedException(sprintf('A ticket that poses a %s would open nothing. %s', $kind, $exception->getMessage()), previous: $exception);
        }
    }
}
