<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

final readonly class UserAuthenticated implements ConcernsAPersonInterface, SessionProvenanceInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'user.authenticated';

    public DateTimeImmutable $occurredAt;

    public Tenant $tenant;

    public RequestContext $context;

    public function __construct(
        public AuthenticationSession $session,
    ) {
        $this->occurredAt = $session->establishedAt;
        $this->tenant = $session->tenant;
        $this->context = $session->createdFrom;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->session->user->subject;
    }

    public function sessionId(): Uuid
    {
        return $this->session->id;
    }
}
