<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * A person drew another set of backup codes for their second factor (architecture §3.1). The codes they
 * had stopped working at that instant, spent or not, so the journal says when: somebody who finds an old
 * list on paper is reading the one line that tells them why it is refused.
 *
 * It carries the credential and never a code: what is shown once is shown to the person who asked, on the
 * page that asked, and what is stored is the hash of each one.
 */
final readonly class BackupCodesRenewed implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'backup_codes.renewed';

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
