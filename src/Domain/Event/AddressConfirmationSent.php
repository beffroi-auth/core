<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Carries the name of the claim, never the address nor the code.
 */
final readonly class AddressConfirmationSent implements ConcernsAPersonInterface
{
    public const string NAME = 'address.confirmation_sent';

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
