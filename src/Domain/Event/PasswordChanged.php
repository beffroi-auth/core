<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * A person replaced their own password, having typed the one they held (architecture §3.1). Not
 * PasswordRehashed, which is this provider writing the same password under a stronger hash and concerns
 * nobody but the deployment: this is an act of the person, and the journal of a person is read to
 * recognise what they did.
 */
final readonly class PasswordChanged implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'password.changed';

    public Tenant $tenant;

    public function __construct(
        public PasswordCredential $credential,
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
