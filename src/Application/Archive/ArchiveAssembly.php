<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Archive\PersonalArchive;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\PersonalArchiveRepositoryInterface;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use function json_encode;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Builds the archive a person asked for (issue #453), off the request.
 *
 * **It knows no table.** What is in the document is what the chapters answer, each one a class tagged into
 * the wiring, so a kind of fact this provider comes to hold about a person is a chapter added and nothing
 * here is edited (ArchiveChapterInterface). One of them lives in the module of the protocol, which is why
 * the chapters are a port of the core: the core cannot read what a protocol calls a consent.
 *
 * **It is built once.** An archive answers the question asked at the moment it was asked, so a second run
 * for the same row does nothing: a retry of the message after the document was written is not a second
 * document, and a row that expired while its message waited is not built at all.
 */
final readonly class ArchiveAssembly
{
    /**
     * The key the document says of itself, which no chapter may take.
     */
    public const string ABOUT = 'archive';

    /**
     * @param iterable<ArchiveChapterInterface> $chapters in the order they are wired, which is the order
     *                                                    the document holds them
     */
    public function __construct(
        private PersonalArchiveRepositoryInterface $archives,
        private iterable $chapters,
        private ClockInterface $clock,
    ) {
    }

    public function assemble(Tenant $tenant, Uuid $id): void
    {
        $archive = $this->archives->find($tenant, $id);
        $now = $this->clock->now();
        if ($archive === null || $archive->isReady() || $archive->hasExpired($now)) {
            return;
        }
        $archive->fill($this->document($archive, $now), $now);
        $this->archives->save($archive);
    }

    /**
     * What the person downloads: what the archive says of itself, then one chapter per kind of fact.
     */
    private function document(PersonalArchive $archive, DateTimeImmutable $builtAt): string
    {
        $document = [
            self::ABOUT => [
                'id' => $archive->id->toRfc4122(),
                'tenant' => $archive->tenant->slug,
                'person' => $archive->user->subject->value,
                'asked_at' => ArchivedFacts::moment($archive->askedAt),
                'built_at' => ArchivedFacts::moment($builtAt),
                'expires_at' => ArchivedFacts::moment($archive->expiresAt),
            ],
        ];
        foreach ($this->chapters as $chapter) {
            $document[$chapter->name()] = $chapter->of($archive->user);
        }

        return json_encode($document, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
