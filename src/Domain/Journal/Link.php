<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Exception\InvalidArgumentException;
use function hash;
use function pack;
use function preg_match;
use function sprintf;
use function str_repeat;
use function strlen;

/**
 * A place in the chain of the acts of a tenant: its position, and the SHA-256 of the link before it and of the act
 * written there, as the database holds it.
 */
final readonly class Link
{
    public const string VERSION = 'beffroi-journal-link/1';

    private const string ABSENT = "\xFF\xFF\xFF\xFF";

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        public int $position,
        public string $hash,
    ) {
        if ($position < 0) {
            throw new InvalidArgumentException(sprintf('A position of the chain is zero or more, "%d" given.', $position));
        }
        if (preg_match('/^[0-9a-f]{64}$/D', $hash) !== 1) {
            throw new InvalidArgumentException('A link of the chain is a SHA-256 in lowercase hexadecimal.');
        }
    }

    public static function beforeTheFirst(): self
    {
        return new self(0, str_repeat('0', 64));
    }

    /**
     * Each field is prefixed with its length, so that no two acts write the same bytes.
     */
    public function then(WrittenAct $act): self
    {
        $position = $this->position + 1;
        $bytes = '';
        foreach ([
            self::VERSION,
            $this->hash,
            (string) $position,
            $act->tenant,
            $act->id,
            $act->act,
            $act->occurredAt->format('Y-m-d H:i:s'),
            $act->subject,
            $act->client,
            $act->author,
            $act->sealedContext,
        ] as $field) {
            $bytes .= $field === null ? self::ABSENT : pack('N', strlen($field)) . $field;
        }

        return new self($position, hash('sha256', $bytes));
    }

    public function equals(self $other): bool
    {
        return $this->position === $other->position && $this->hash === $other->hash;
    }
}
