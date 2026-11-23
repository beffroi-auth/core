<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Enrolment;

use Beffroi\Core\Application\Passkey\PasskeyEnrolment;
use Beffroi\Core\Application\Ticket\TicketRedeeming;
use Beffroi\Core\Domain\Credential\PasskeyCredential;
use Beffroi\Core\Domain\Enrolment\EnrolmentTicket;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Exception\PasskeyAttestationRefusedException;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\EnrolmentTicketRepositoryInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialCreationOptions;

final readonly class EnrolmentTicketRedemption
{
    public function __construct(
        private EnrolmentTicketRepositoryInterface $tickets,
        private TicketRedeeming $redeeming,
        private PasskeyEnrolment $enrolment,
    ) {
    }

    /**
     * @throws TicketRefusedException
     */
    public function redeem(Tenant $tenant, Uuid $id, #[SensitiveParameter] string $value): EnrolmentTicket
    {
        return $this->redeeming->redeem($this->tickets, $tenant, $id, $value);
    }

    /**
     * The ticket is spent once the passkey is enrolled and never before.
     *
     * @param string $answer the attestation response, as the integration received it
     *
     * @throws CredentialEnrolmentFailedException
     * @throws PasskeyAttestationRefusedException
     * @throws TicketRefusedException             when another redemption spent the ticket first
     */
    public function enrol(
        EnrolmentTicket $ticket,
        string $name,
        PublicKeyCredentialCreationOptions $challenge,
        string $answer,
        RequestContext $context,
    ): PasskeyCredential {
        return $this->redeeming->spendOnceWritten(
            $this->tickets,
            $ticket,
            fn (): PasskeyCredential => $this->enrolment->complete($ticket->user, $name, $challenge, $answer, $context),
        );
    }
}
