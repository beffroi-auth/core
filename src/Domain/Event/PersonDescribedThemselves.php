<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

final readonly class PersonDescribedThemselves implements ConcernsAPersonInterface
{
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
