<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use DateTimeImmutable;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * The credential is gone, row and secret (issue #327): what the tenant raised its floor above is not left
 * dormant in the store, where a password nobody can use any more would still be a password to break if the
 * database ever leaked. A revocation leaves the row and says "never again"; this says "no longer here".
 *
 * It is the act the journal of that person keeps of it, and the only trace that remains: the row it names
 * cannot be read back, which is the point of the act being written at all.
 */
final readonly class CredentialForgotten implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'credential.forgotten';

    public Tenant $tenant;

    public function __construct(
        public Credential $credential,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $credential->tenant;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->credential->user->subject;
    }
}
