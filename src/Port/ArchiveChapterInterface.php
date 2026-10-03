<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\User;

/**
 * One chapter of the archive a person asks for (issue #453): what this provider holds of them under one
 * name, read in the clear.
 *
 * **A chapter per kind of fact, tagged into the wiring**, so that the archive of a person is derived from
 * what is wired and from nothing kept beside it: a table this provider comes to hold about a person is a
 * chapter added and a line in the wiring, and the use case that assembles the document is not edited. It
 * is a port of the core and implemented in both packages: what the protocol calls a consent is a chapter
 * of the module, which the core could not read.
 *
 * **A chapter is a list, always.** One that is sometimes an object and sometimes a list is a document a
 * reader has to know in advance, where RGPD article 20 asks for one a program can read; the chapter that
 * holds exactly one record answers a list of one.
 *
 * **A chapter names the fields it writes** and never hands an object to a serializer: a field added to an
 * entity would otherwise walk into the archive the day it is added, which is how the hash of a password
 * ends up in a file a person downloads.
 */
interface ArchiveChapterInterface
{
    /**
     * The name of the chapter in the document, which is also the key the page of the account translates to
     * say what is in it. "archive" is taken, being what the document says of itself.
     */
    public function name(): string;

    /**
     * @return list<array<string, mixed>> what this provider holds of that person under this name, in the
     *                                   clear, empty when it holds nothing
     */
    public function of(User $user): array;
}
