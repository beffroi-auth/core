<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Event\PersonDescribedThemselves;
use Beffroi\Core\Domain\Identity\ClaimChange;
use Beffroi\Core\Domain\Identity\MailEncryptionKey;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\MailEncryptionRefusedException;
use Beffroi\Core\Port\ClaimHistoryInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * A person gives the certificate or the key the mail of the provider is encrypted to, one at a time, or takes it back:
 * a change of their record, which their history keeps by its format and its fingerprint.
 */
final readonly class MailEncryptionChoice
{
    /**
     * The name of what changed in the history of the person.
     */
    public const string CHANGED = 'mail_encryption';

    public function __construct(
        private UserRepositoryInterface $users,
        private ClaimHistoryInterface $history,
        private EventDispatcherInterface $events,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param MailEncryptionKey|null $key null writes the mail in clear again
     *
     * @throws MailEncryptionRefusedException when the key is not one the mail to this person may be encrypted to
     */
    public function choose(User $user, ?MailEncryptionKey $key, RequestContext $context): void
    {
        $now = $this->clock->now();
        $was = $user->mailEncryption?->described();
        if ($key === null) {
            $user->writeMailInClear($now);
        } else {
            $user->encryptMailTo($key, $now);
        }
        $became = $user->mailEncryption?->described();
        $this->users->save($user);
        if ($was !== $became) {
            $this->history->record(new ClaimChange(Uuid::v7(), $user, self::CHANGED, $was, $became, $now, $context));
        }
        $this->events->dispatch(new PersonDescribedThemselves($user, self::CHANGED, $became === null, $now, $context));
    }
}
