<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Archive;

use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * Everything this provider knows of one person, in one file they asked for (RGPD articles 15 and 20,
 * issue #453): a row from the moment they ask, a document once it has been built, and nothing at all once
 * it has expired.
 *
 * **It is the one file where every sealed column stands in clear at once**, which is what makes it the
 * most dangerous row of this schema and why three rules hold it.
 *
 * - **It is sealed at rest like everything else** (decision 0011). The column is sealed under the data key
 *   of the tenant, in a scope of its own, and it carries no exemption: a file that gathers what every
 *   other family seals separately cannot be the one thing a dump reads.
 * - **It expires, and the duration is not configurable.** Every day it exists is a day that file exists,
 *   so a deployment is not offered the chance to keep it for a year (decision 0006). A week is what a
 *   person who asked on a Friday has.
 * - **The expiry is counted from the moment it was asked for** and not from the moment it was built, so a
 *   row always carries the date it goes away on, whatever happened to the build: one that never ran is
 *   forgotten on the same date as one that did.
 *
 * It names itself by its identifier and never by the person, because what a person downloads carries that
 * name in a header a proxy writes to a log.
 */
final class PersonalArchive
{
    /**
     * How long an archive is kept once it has been asked for. A week, which is what a person who asked on
     * a Friday has, and a figure in code rather than an option: see above.
     */
    public const int KEPT_FOR_DAYS = 7;

    /**
     * What a person downloads, which is what RGPD article 20 asks for: a format that is structured,
     * commonly used and machine readable.
     */
    public const string MEDIA_TYPE = 'application/json';

    /**
     * The extension the plain file carries. A container that wraps it names its own (issue #483,
     * ArchiveContainerInterface), and the name itself is built here either way.
     */
    public const string EXTENSION = 'json';

    /**
     * Restated from the person, as everything that hangs off a user restates it (decision 0002).
     */
    public readonly Tenant $tenant;

    public private(set) ?string $content = null;

    public private(set) ?DateTimeImmutable $builtAt = null;

    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly DateTimeImmutable $askedAt,
        public readonly DateTimeImmutable $expiresAt,
    ) {
        if ($expiresAt <= $askedAt) {
            throw new InvalidArgumentException('An archive expires after it was asked for.');
        }
        $this->tenant = $user->tenant;
    }

    /**
     * The archive a person has just asked for: a row, a date it goes away on, and no document yet.
     */
    public static function asked(Uuid $id, User $user, DateTimeImmutable $now): self
    {
        return new self($id, $user, $now, $now->modify(sprintf('+%d days', self::KEPT_FOR_DAYS)));
    }

    /**
     * @throws InvalidArgumentException if it has already been built, an archive being built once: a second
     *                                  build would answer a question asked at another moment
     */
    public function fill(string $content, DateTimeImmutable $now): void
    {
        if ($this->content !== null) {
            throw new InvalidArgumentException('An archive is built once.');
        }
        if ($content === '') {
            throw new InvalidArgumentException('An archive holds a document.');
        }
        $this->content = $content;
        $this->builtAt = $now;
    }

    public function isReady(): bool
    {
        return $this->content !== null;
    }

    public function hasExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    /**
     * The name of the file a person downloads: the archive and never the person, a file name travelling in
     * a header that proxies and browsers write down.
     *
     * @param string $extension what the file is, the plain document unless a container wrapped it (issue
     *                          #483): the name is one rule and not one per form of the file
     */
    public function fileName(string $extension = self::EXTENSION): string
    {
        return sprintf('archive-%s.%s', $this->id->toRfc4122(), $extension);
    }
}
