<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Ticket;

use Beffroi\Core\Application\Authentication\OtherSessionsTermination;
use Beffroi\Core\Application\Credential\ActivePassword;
use Beffroi\Core\Application\Identity\UserProvisioner;
use Beffroi\Core\Domain\Credential\CredentialChange;
use Beffroi\Core\Domain\Credential\PasswordChangeRefusal;
use Beffroi\Core\Domain\Credential\PlainPassword;
use Beffroi\Core\Domain\Event\CredentialEnrolled;
use Beffroi\Core\Domain\Event\PasswordChanged;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Domain\Ticket\PasswordTicket;
use Beffroi\Core\Exception\CredentialEnrolmentFailedException;
use Beffroi\Core\Exception\PasswordChangeRefusedException;
use Beffroi\Core\Exception\PasswordRefusedException;
use Beffroi\Core\Exception\TicketRefusedException;
use Beffroi\Core\Port\CredentialChangeNotifierInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\PasswordTicketRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * A password ticket presented, and spent on the password the person poses (decision 0026): it replaces the one they
 * held, or is their first, and every session of theirs ends once it is written, since the secret it replaces may have
 * been lost to somebody.
 */
final readonly class PasswordTicketRedemption
{
    public function __construct(
        private PasswordTicketRepositoryInterface $tickets,
        private TicketRedeeming $redeeming,
        private ActivePassword $activePassword,
        private PasswordHasherInterface $hasher,
        private UserProvisioner $provisioner,
        private OtherSessionsTermination $sessions,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private CredentialChangeNotifierInterface $notifier,
    ) {
    }

    /**
     * @throws TicketRefusedException
     */
    public function redeem(Tenant $tenant, Uuid $id, #[SensitiveParameter] string $value): PasswordTicket
    {
        return $this->redeeming->redeem($this->tickets, $tenant, $id, $value);
    }

    /**
     * The ticket is spent once the password is written and never before, and each session of the person ends through
     * `SessionTerminator`, its relying parties told by the back-channel and its refresh tokens revoked.
     *
     * @throws PasswordChangeRefusedException     when the password posed is the one the person holds
     * @throws CredentialEnrolmentFailedException when the floor of the tenant or the domain of the person admits no password
     * @throws PasswordRefusedException           when a list of compromised passwords counts it or cannot be asked
     */
    public function spend(PasswordTicket $ticket, #[SensitiveParameter] PlainPassword $password, RequestContext $context): void
    {
        $held = $this->activePassword->of($ticket->user);
        if ($held !== null && $this->hasher->verify($held->hash, $password->value)) {
            throw new PasswordChangeRefusedException(PasswordChangeRefusal::SameAsBefore, 'The password posed is the one already held.');
        }
        $credential = $this->provisioner->setPassword($ticket->user, $password);
        $now = $this->clock->now();
        $act = $held === null ? new CredentialEnrolled($credential, $now, $context) : new PasswordChanged($credential, $now, $context);
        $this->events->dispatch($act);
        $this->notifier->tell(new CredentialChange($ticket->user, $act->name(), $now, $credential->kind()));
        $this->redeeming->spend($this->tickets, $ticket);
        $this->sessions->endEvery($ticket->user, $context);
    }
}
