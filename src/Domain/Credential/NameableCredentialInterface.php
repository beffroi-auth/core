<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * A credential the person who holds it names (architecture §3.1): one passkey is told from another by the
 * name its holder gave it, so that name is theirs to correct, and a page that lists them lets them.
 *
 * A kind whose name nobody chooses does not declare this: a password is not named, and a second factor is
 * the one the account has.
 */
interface NameableCredentialInterface
{
    /**
     * The name its holder gave it, which is never empty: a credential of such a kind is named when it is
     * enrolled, so a page that lists them has something to show before anybody corrects anything.
     */
    public string $name { get; }

    /**
     * @throws InvalidArgumentException when the name is empty or longer than the kind holds
     */
    public function rename(string $name): void;
}
