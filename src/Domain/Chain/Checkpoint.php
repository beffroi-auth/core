<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * What the provider signs of a chain of a tenant: its head, the last link the retention forgot, before which nothing is
 * kept, how far the whole chain was last walked and when, and the first position that walk found not following.
 */
final readonly class Checkpoint
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        public Uuid $tenant,
        public string $chain,
        public Link $head,
        public Link $keptAfter,
        public Link $walked,
        public DateTimeImmutable $walkedAt,
        public DateTimeImmutable $signedAt,
        public ?int $broken = null,
    ) {
        if ($chain === '') {
            throw new InvalidArgumentException('A checkpoint names its chain.');
        }
        if ($keptAfter->position > $head->position) {
            throw new InvalidArgumentException('A chain keeps nothing past its head.');
        }
        if ($walked->position > $head->position) {
            throw new InvalidArgumentException('A chain is walked no further than its head.');
        }
    }

    /**
     * @return array{tenant: string, chain: string, head: array{position: int, hash: string}, kept_after: array{position: int, hash: string}, walked: array{position: int, hash: string, at: int}, broken: int|null, iat: int}
     */
    public function toDocument(): array
    {
        return [
            'tenant' => $this->tenant->toRfc4122(),
            'chain' => $this->chain,
            'head' => self::linkDocument($this->head),
            'kept_after' => self::linkDocument($this->keptAfter),
            'walked' => [...self::linkDocument($this->walked), 'at' => $this->walkedAt->getTimestamp()],
            'broken' => $this->broken,
            'iat' => $this->signedAt->getTimestamp(),
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromDocument(Document $document): self
    {
        $tenant = $document->nullableString('tenant');
        $chain = $document->nullableString('chain');
        $signedAt = $document->nullableInt('iat');
        $walked = new Document($document->nested('walked') ?? []);
        $walkedAt = $walked->nullableInt('at');
        if ($tenant === null || ! Uuid::isValid($tenant) || $chain === null || $signedAt === null || $walkedAt === null) {
            throw new InvalidArgumentException('A checkpoint of a chain names its tenant, its chain, and when it was walked and signed.');
        }

        return new self(
            Uuid::fromString($tenant),
            $chain,
            self::link(new Document($document->nested('head') ?? [])),
            self::link(new Document($document->nested('kept_after') ?? [])),
            self::link($walked),
            new DateTimeImmutable('@' . $walkedAt),
            new DateTimeImmutable('@' . $signedAt),
            $document->nullableInt('broken'),
        );
    }

    /**
     * @return array{position: int, hash: string}
     */
    private static function linkDocument(Link $link): array
    {
        return [
            'position' => $link->position,
            'hash' => $link->hash,
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function link(Document $document): Link
    {
        $position = $document->nullableInt('position');
        $hash = $document->nullableString('hash');
        if ($position === null || $hash === null) {
            throw new InvalidArgumentException('A link of a checkpoint names its position and its hash.');
        }

        return new Link($position, $hash);
    }
}
