<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * A person said something of themselves, on a page of their own account (issue #452).
 *
 * It carries the name of the claim and **never the value**: the journal of the acts writes this act down
 * (§3.5), and what a person said of themselves is a personal datum that belongs in the row of that person
 * and not in a second copy nobody would think to erase. That somebody changed their own name is the act;
 * what the name is now is read where names are read.
 */
final readonly class PersonDescribedThemselves implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'person.described_themselves';

    public Tenant $tenant;

    public function __construct(
        public User $user,
        public string $claim,
        public bool $takenBack,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
        $this->tenant = $user->tenant;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->user->subject;
    }
}
