<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Application\Authentication\OtherSessionsTermination;
use Beffroi\Core\Application\Totp\TotpEnrolment;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Credential\TotpProvisioning;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Domain\Ticket\TotpTicket;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\TotpTicketRepositoryInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * A one-time password ticket presented, and spent on the authenticator app the person enrols (decision 0026): the app
 * replaces the one they held, or is their first, and every session of theirs ends once it is enrolled, since the
 * secret it replaces may have been lost to somebody.
 */
final readonly class TotpTicketRedemption
{
    public function __construct(
        private TotpTicketRepositoryInterface $tickets,
        private TicketRedeeming $redeeming,
        private TotpEnrolment $enrolment,
        private OtherSessionsTermination $sessions,
    ) {
    }

    /**
     * @throws TicketRefusedException
     */
    public function redeem(Tenant $tenant, Uuid $id, #[SensitiveParameter] string $value): TotpTicket
    {
        return $this->redeeming->redeem($this->tickets, $tenant, $id, $value);
    }

    /**
     * @throws CredentialEnrolmentFailedException when the floor of the tenant or the domain of the person admits no app
     */
    public function begin(TotpTicket $ticket): TotpProvisioning
    {
        return $this->enrolment->beginInPlaceOfTheOneHeld($ticket->user);
    }

    /**
     * The ticket is spent once the app is enrolled and never before, and each session of the person ends through
     * `SessionTerminator`, its relying parties told by the back-channel and its refresh tokens revoked.
     *
     * @throws CredentialEnrolmentFailedException when the code does not match, or the floor or the domain admits no app
     * @throws KmsUnavailableException
     * @throws TicketRefusedException             when another redemption spent the ticket first
     */
    public function spend(TotpTicket $ticket, TotpProvisioning $provisioning, string $code, RequestContext $context): TotpCredential
    {
        $credential = $this->redeeming->spendOnceWritten(
            $this->tickets,
            $ticket,
            fn (): TotpCredential => $this->enrolment->completeInPlaceOfTheOneHeld($ticket->user, $provisioning, $code, $context),
        );
        $this->sessions->endEvery($ticket->user, $context);

        return $credential;
    }
}
