<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Carries the names of what changed, never the channels: the history of the person holds what they chose before.
 */
final readonly class NotificationChannelsChosen implements ConcernsAPersonInterface
{
    public const string NAME = 'notification_channels.chosen';

    /**
     * @param list<string> $changed the names the history of the person writes the changes under
     */
    public function __construct(
        public Tenant $tenant,
        public Subject $person,
        public array $changed,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->person;
    }
}
