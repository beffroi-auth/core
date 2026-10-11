<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * One of the backup codes of this person was spent (issue #571): a code they no longer have, and the
 * second factor of somebody who did not have their authenticator at hand.
 *
 * **It is an act of its own and not a flag on TotpUsed**, because the journal holds four facts and two
 * names and drops anything beside them: a boolean the row cannot carry is a kind the page cannot name, and
 * a kind that behaves differently is a class. What they prove is the same thing ("otp"), which is the
 * authentication's to say (TotpAuthentication); what the person reads of their own account is not the same
 * line at all, a code spent being worth renewing the set (BackupCodesRenewed).
 */
final readonly class BackupCodeUsed implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'backup_code.used';

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
