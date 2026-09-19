<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Credential;

use Heidi\Core\Domain\Credential\Credential;
use Heidi\Core\Domain\Credential\RevocableCredentialInterface;
use Heidi\Core\Domain\Event\CredentialRevoked;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A credential leaves use without leaving its row, whatever its kind: revoked by the user or by an
 * operator, it never authenticates again. What happens to the sessions it opened is the caller's policy.
 */
final readonly class CredentialRevocation
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * A second revocation changes nothing and announces nothing.
     */
    public function revoke(Credential&RevocableCredentialInterface $credential, RequestContext $context): void
    {
        if ($credential->isRevoked()) {
            return;
        }
        $now = $this->clock->now();
        $credential->revoke($now);
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialRevoked($credential, $now, $context));
    }
}
