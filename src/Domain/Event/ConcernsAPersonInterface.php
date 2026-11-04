<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;

interface ConcernsAPersonInterface extends DomainEventInterface
{
    public function subject(): ?Subject;
}
