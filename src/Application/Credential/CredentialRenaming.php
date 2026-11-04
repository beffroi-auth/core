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

final readonly class CredentialRenaming
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @throws InvalidArgumentException
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
