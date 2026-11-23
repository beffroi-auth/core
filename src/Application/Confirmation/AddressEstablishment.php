<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Confirmation;

use Beffroi\Core\Application\Identity\Member\Said;
use Beffroi\Core\Domain\Confirmation\AddressConfirmation;
use Beffroi\Core\Domain\Confirmation\ConfirmableAddressInterface;
use Beffroi\Core\Domain\Event\AddressConfirmationSent;
use Beffroi\Core\Domain\Event\AddressConfirmationWithheld;
use Beffroi\Core\Domain\Event\AddressEstablished;
use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\AddressConfirmationRefusedException;
use Beffroi\Core\Exception\AddressConfirmationWithheldException;
use Beffroi\Core\Port\AddressConfirmationRepositoryInterface;
use Beffroi\Core\Port\AddressConfirmationSenderInterface;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ClaimHistoryInterface;
use Beffroi\Core\Port\ConfirmationSendLimiterInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * NIST SP 800-63B-4 §4.2.1.2
 *
 * One kind of address of a person, established only once the code or the link sent there comes back from the person
 * it was sent for. What the source of the person holds is not established here: its next sign-in writes it back.
 */
final readonly class AddressEstablishment
{
    public function __construct(
        private ConfirmableAddressInterface $kind,
        private AddressConfirmationRepositoryInterface $confirmations,
        private AddressConfirmationSenderInterface $sender,
        private ConfirmationSendLimiterInterface $sends,
        private AddressConfirmationIssuing $issuing,
        private AddressConfirmationRedeeming $redeeming,
        private UserRepositoryInterface $users,
        private ClaimHistoryInterface $history,
        private AtomicWritesInterface $writes,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    public function claim(): string
    {
        return $this->kind->claim();
    }

    /**
     * The address a confirmation would establish, or null when the person holds none of the kind, holds it verified,
     * cannot sign in, or holds it from their source.
     */
    public function awaits(User $user): ?string
    {
        $address = $this->kind->of($user);
        if ($address === null || $this->kind->isVerified($user) || ! $user->isEnabled() || $user->source->holds($this->kind->claim())) {
            return null;
        }

        return $address;
    }

    /**
     * Counted before anything is drawn, so that a confirmation withheld leaves the one sent before it open.
     *
     * @throws AddressConfirmationRefusedException when no address of the kind awaits a confirmation
     * @throws AddressConfirmationWithheldException when the person or the address has had every confirmation its budget allows
     */
    public function send(User $user, RequestContext $context): void
    {
        $address = $this->awaits($user) ?? throw new AddressConfirmationRefusedException('No address of this kind awaits a confirmation.');
        if (! $this->sends->spend($user, $address)) {
            $this->events->dispatch(new AddressConfirmationWithheld($user, $this->kind->claim(), $this->clock->now(), $context));

            throw new AddressConfirmationWithheldException('The person or the address has had every confirmation its budget allows.');
        }
        $issued = $this->issuing->issue($this->confirmations, $this->kind, $user, $address);
        $this->sender->send($issued);
        $this->events->dispatch(new AddressConfirmationSent($user, $this->kind->claim(), $issued->confirmation->createdAt, $context));
    }

    /**
     * @throws AddressConfirmationRefusedException
     */
    public function confirm(User $user, #[SensitiveParameter] string $code, RequestContext $context): void
    {
        $this->establish($this->redeeming->byCode($this->confirmations, $this->kind, $user, $code), $context);
    }

    /**
     * The link opens a page and confirms nothing; the person it was sent for confirms there.
     *
     * @throws AddressConfirmationRefusedException
     */
    public function confirmByLink(User $user, Uuid $id, #[SensitiveParameter] string $value, RequestContext $context): void
    {
        $this->establish($this->redeeming->byLink($this->confirmations, $this->kind, $user, $id, $value), $context);
    }

    /**
     * @throws AddressConfirmationRefusedException
     */
    private function establish(AddressConfirmation $confirmation, RequestContext $context): void
    {
        $user = $confirmation->user;
        if ($user->source->holds($this->kind->claim())) {
            throw new AddressConfirmationRefusedException(AddressConfirmationRedeeming::REFUSED);
        }
        $now = $this->clock->now();
        $verification = $this->kind->claim() . '_verified';
        $was = $this->kind->isVerified($user);
        $this->writes->atomically(function () use ($confirmation, $user, $now, $verification, $was, $context): void {
            $confirmation->spend($now);
            $this->kind->establish($user, $now);
            $this->users->save($user);
            $this->confirmations->save($confirmation);
            if (! $was) {
                $this->history->record(new ClaimChange(Uuid::v7(), $user, $verification, Said::written(false), Said::written(true), $now, $context));
            }
        });
        $this->events->dispatch(new AddressEstablished($user, $this->kind->claim(), $now, $context));
    }
}
