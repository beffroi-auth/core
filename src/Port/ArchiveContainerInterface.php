<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Archive\ArchivePassword;
use Beffroi\Core\Exception\RuntimeException;

/**
 * What wraps the archive of a person under the passphrase they chose (issue #483).
 *
 * **It is a port and not a method of the archive**, because what a container is belongs to what the
 * deployment's PHP can write and not to the model: a second format is a class that implements this and a
 * line of the wiring, and the page that offers the file reads what is wired rather than naming a format.
 *
 * **It wraps in the request that serves the file, and never before.** A passphrase given when the archive
 * was asked for would travel to a broker, which is the one thing a passphrase may never do, so the row
 * stays sealed under the data key of the tenant (decision 0011) and what a browser is handed is made here.
 *
 * **The plain file stays.** RGPD article 20 asks for a format that is structured, commonly used and
 * machine readable, and a container only this provider can make is not commonly used, so a person chooses
 * between the two rather than being given one.
 */
interface ArchiveContainerInterface
{
    /**
     * The media type of what wrap() returns.
     */
    public function mediaType(): string;

    /**
     * The extension of the file this container writes, which is what tells it from the plain one in the
     * directory a person downloaded both into.
     */
    public function extension(): string;

    /**
     * @param string $document the archive as it is stored
     * @param string $name     the name the document carries inside the container
     *
     * @throws RuntimeException if this container cannot wrap, which is never answered by handing the
     *                          document out unwrapped (decision 0006)
     */
    public function wrap(string $document, string $name, ArchivePassword $password): string;
}
