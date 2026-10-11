<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * A code the authenticator of this person computed was accepted. One of their backup codes is another act
 * (BackupCodeUsed): what the two prove is the same thing, what they say of an account is not.
 */
final readonly class TotpUsed implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'totp.used';

    public Tenant $tenant;

    public function __construct(
        public TotpCredential $credential,
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
