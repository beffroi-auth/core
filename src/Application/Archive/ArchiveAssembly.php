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

final readonly class ArchiveAssembly
{
    /**
     * No chapter may take this key.
     */
    public const string ABOUT = 'archive';

    /**
     * @param iterable<ArchiveChapterInterface> $chapters
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
