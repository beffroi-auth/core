<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\NameableCredentialInterface;
use Beffroi\Core\Domain\Event\CredentialRenamed;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The person who holds a credential names it, and names it again: what tells one of their passkeys from
 * another is a name they wrote, so correcting it is their own act and not an operator's.
 *
 * What the credential proves is untouched, which is why this is not the enrolment of a new one.
 */
final readonly class CredentialRenaming
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * A name that is the one it already holds changes nothing and announces nothing.
     *
     * @throws InvalidArgumentException when the name is one the kind does not hold
     */
    public function rename(Credential&NameableCredentialInterface $credential, string $name, RequestContext $context): void
    {
        $was = $credential->name;
        $credential->rename($name);
        if ($credential->name === $was) {
            return;
        }
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialRenamed($credential, $was, $this->clock->now(), $context));
    }
}
