<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

/**
 * A chapter names the fields it writes and never hands an object to a serializer: a field added to an entity
 * would otherwise reach the archive, a password hash included.
 */
interface ArchiveChapterInterface
{
    /**
     * Also the key the page of the account translates; "archive" is taken by the document itself.
     */
    public function name(): string;

    /**
     * @return list<array<string, mixed>> in the clear
     */
    public function of(User $user): array;
}
