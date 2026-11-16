<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;

/**
 * An act that names who did it when an administrator of the tenant did it to a person: read them, ended a session of
 * theirs or revoked a credential of theirs through the administration API (decision 0038). The journal writes the
 * author beside the person, both by the subject their accounts carry, which names them without saying anything about
 * them.
 *
 * An act the person did, or nobody this provider knows, names no author: a command run on the host names its operator
 * in its provenance (architecture §5.5), and an act of the person is theirs already.
 */
interface NamesItsAuthorInterface extends DomainEventInterface
{
    /**
     * @return Subject|null the administrator who did it, null when nobody but the person or the host did
     */
    public function author(): ?Subject;
}
