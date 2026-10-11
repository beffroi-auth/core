<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * The password of this person was typed and accepted (issue #571).
 *
 * **Every proof is an act of its own**, so that the journal of a person says how somebody got in and not
 * only that they did: the session that opens says that somebody signed in (UserAuthenticated), and this
 * says that what they presented was the password. A password that is the first step of a login is accepted
 * here as much as one that signs somebody in by itself: it was accepted whether or not a code follows.
 *
 * Not PasswordChanged, which is a person replacing the one they hold, and not PasswordRehashed, which is
 * this provider storing the same password under a stronger hash.
 */
final readonly class PasswordUsed implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'password.used';

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
