<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\CredentialChange;
use Beffroi\Core\Domain\Credential\RevocableCredentialInterface;
use Beffroi\Core\Domain\Event\CredentialRevoked;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\CredentialChangeNotifierInterface;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Ends none of the sessions the credential opened: that is the caller's policy.
 */
final readonly class CredentialRevocation
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private CredentialChangeNotifierInterface $notifier,
    ) {
    }

    public function revoke(Credential&RevocableCredentialInterface $credential, RequestContext $context): void
    {
        $this->revokeOnce($credential, $context, null);
    }

    /**
     * The act of the revocation names the administrator of the tenant who revoked the credential (decision 0038).
     */
    public function revokeByAnAdministrator(Credential&RevocableCredentialInterface $credential, Subject $administrator, RequestContext $context): void
    {
        $this->revokeOnce($credential, $context, $administrator);
    }

    private function revokeOnce(Credential&RevocableCredentialInterface $credential, RequestContext $context, ?Subject $administrator): void
    {
        if ($credential->isRevoked()) {
            return;
        }
        $now = $this->clock->now();
        $credential->revoke($now);
        $this->credentials->save($credential);
        $this->events->dispatch(new CredentialRevoked($credential, $now, $context, $administrator));
        $this->notifier->tell(new CredentialChange($credential->user, CredentialRevoked::NAME, $now, $credential->kind()));
    }
}
