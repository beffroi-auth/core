<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use function mb_strlen;
use function sprintf;
use Symfony\Component\Uid\Uuid;

/**
 * No bag a caller fills: a secret would be one careless line away from the journal.
 * The identifier is a UUIDv7, so the journal sorts chronologically by its primary key.
 */
final class JournalEntry implements ClassifiedFieldsInterface
{
    public const int ACT_LENGTH = 64;

    /**
     * In characters, for the reason of a refusal and the method it refused.
     */
    public const int WORD_LENGTH = 64;

    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public readonly string $act,
        public readonly DateTimeImmutable $occurredAt,
        public readonly RequestContext $context,
        public readonly ?Subject $subject = null,
        public readonly ?string $client = null,
        public readonly ?Subject $author = null,
        public readonly ?string $reason = null,
        public readonly ?string $method = null,
    ) {
        if ($act === '') {
            throw new InvalidArgumentException('An act of the journal has a name.');
        }
        if ($client === '') {
            throw new InvalidArgumentException('An act of the journal names a client or names none, never an empty one.');
        }
        foreach ([$reason, $method] as $word) {
            if ($word !== null && ($word === '' || mb_strlen($word) > self::WORD_LENGTH)) {
                throw new InvalidArgumentException(sprintf(
                    'The reason of a refusal and the method it refused are words of %d characters at most, never empty.',
                    self::WORD_LENGTH,
                ));
            }
        }
    }

    /**
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'act' => new NoPersonalDatum('the name of the act, which an event gives itself and which names no person'),
            'occurredAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'context' => new Sealed('journal'),
            'subject' => new NoPersonalDatum(Reason::THE_SUBJECT),
            'client' => new NoPersonalDatum('the client the act concerned, by the identifier it was registered under'),
            'author' => new NoPersonalDatum(Reason::THE_SUBJECT),
            'reason' => new NoPersonalDatum('the reason of a refusal, a word of the vocabulary of this provider'),
            'method' => new NoPersonalDatum('the method a refusal refused, by the name this provider gives it'),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
        ];
    }
}
