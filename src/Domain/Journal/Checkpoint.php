<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\Encoding\Document;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * What the provider signs of the chain of a tenant: its head, and the last link the retention forgot, before which
 * nothing is kept.
 */
final readonly class Checkpoint
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        public Uuid $tenant,
        public Link $head,
        public Link $keptAfter,
        public DateTimeImmutable $signedAt,
    ) {
        if ($keptAfter->position > $head->position) {
            throw new InvalidArgumentException('A chain keeps nothing past its head.');
        }
    }

    /**
     * @return array{tenant: string, head: array{position: int, hash: string}, kept_after: array{position: int, hash: string}, iat: int}
     */
    public function toDocument(): array
    {
        return [
            'tenant' => $this->tenant->toRfc4122(),
            'head' => [
                'position' => $this->head->position,
                'hash' => $this->head->hash,
            ],
            'kept_after' => [
                'position' => $this->keptAfter->position,
                'hash' => $this->keptAfter->hash,
            ],
            'iat' => $this->signedAt->getTimestamp(),
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromDocument(Document $document): self
    {
        $tenant = $document->nullableString('tenant');
        $signedAt = $document->nullableInt('iat');
        if ($tenant === null || ! Uuid::isValid($tenant) || $signedAt === null) {
            throw new InvalidArgumentException('A checkpoint of the journal names its tenant and when it was signed.');
        }

        return new self(
            Uuid::fromString($tenant),
            self::link(new Document($document->nested('head') ?? [])),
            self::link(new Document($document->nested('kept_after') ?? [])),
            new DateTimeImmutable('@' . $signedAt),
        );
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
