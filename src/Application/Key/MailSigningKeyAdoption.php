<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Key;

use Beffroi\Core\Domain\Event\MailSigningKeyRemoved;
use Beffroi\Core\Domain\Event\MailSigningKeySet;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Key\MailSigningKey;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\MailSigningKeyRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * An administrator of the tenant sets the key of a format its mail is signed with, or removes one (decision 0038): the
 * key it replaces signs nothing from then on and stays served until it is removed.
 */
final readonly class MailSigningKeyAdoption
{
    public function __construct(
        private MailSigningKeyRepositoryInterface $keys,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws RuntimeException when the storage refuses it
     */
    public function adoptByAnAdministrator(MailSigningKey $key, Subject $administrator, RequestContext $context): void
    {
        $now = $this->clock->now();
        $replaced = $this->keys->current($key->tenant, $key->kind);
        // The new key is written first: a failure between the two writes leaves two keys current and the newest signing.
        $this->keys->save($key);
        if ($replaced !== null && $replaced->fingerprint !== $key->fingerprint) {
            $replaced->replace($now);
            $this->keys->save($replaced);
        }
        $this->events->dispatch(new MailSigningKeySet($key->tenant, $key->kind, $key->fingerprint, $now, $context, $administrator));
    }

    /**
     * @throws RuntimeException when the storage refuses it
     */
    public function removeByAnAdministrator(MailSigningKey $key, Subject $administrator, RequestContext $context): void
    {
        $this->keys->remove($key);
        $this->events->dispatch(new MailSigningKeyRemoved($key->tenant, $key->kind, $key->fingerprint, $this->clock->now(), $context, $administrator));
    }
}
