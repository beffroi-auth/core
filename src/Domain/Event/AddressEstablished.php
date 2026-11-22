<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * NIST SP 800-63B-4 §4.2.1.2: the person gave back the confirmation sent to an address of theirs. Carries the name of
 * the claim, never the address.
 */
final readonly class AddressEstablished implements ConcernsAPersonInterface
{
    public const string NAME = 'address.established';

    public Tenant $tenant;

    public function __construct(
        public User $user,
        public string $claim,
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
