<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;

/**
 * An act somebody did to a person who is not that person, which names who did it: the administrator who read them
 * through the administration API of their tenant (decision 0038). The journal writes the author beside the person,
 * both by the subject their accounts carry, which names them without saying anything about them.
 *
 * An event whose author is the person it concerns, or nobody this provider knows, does not carry this: a command run
 * on the host names its operator in its provenance (architecture §5.5), and an act of the person is theirs already.
 */
interface NamesItsAuthorInterface extends DomainEventInterface
{
    public function author(): Subject;
}
