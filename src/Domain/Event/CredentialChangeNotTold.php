<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * NIST SP 800-63B-4 §4.6: a channel gave up on the notice of a change to the credentials of the person.
 */
final readonly class CredentialChangeNotTold implements ConcernsAPersonInterface
{
    public const string NAME = 'credential_change.not_told';

    /**
     * @param string $act     the name the journal wrote the change down under
     * @param string $channel the name of the channel that gave up
     * @param string $reason  why its last attempt failed
     */
    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public RequestContext $context,
        public Subject $subject,
        public string $act,
        public string $channel,
        public string $reason,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->subject;
    }
}
