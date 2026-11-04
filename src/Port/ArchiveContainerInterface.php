<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Archive\ArchivePassword;
use Beffroi\Core\Exception\RuntimeException;

/**
 * Wraps the archive of a person under the passphrase they chose, in the request that serves the file: a
 * passphrase never travels to a broker.
 */
interface ArchiveContainerInterface
{
    public function mediaType(): string;

    public function extension(): string;

    /**
     * @param string $name the name the document carries inside the container
     *
     * @throws RuntimeException when it cannot wrap, which is never answered by handing the document out unwrapped
     */
    public function wrap(string $document, string $name, ArchivePassword $password): string;
}
