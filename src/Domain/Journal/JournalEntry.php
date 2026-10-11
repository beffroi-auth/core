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
use Symfony\Component\Uid\Uuid;

/**
 * One act of this provider, written down (issue #454): who did what, when, and from where.
 *
 * **Four facts and two names, and nothing else.** No bag a caller fills, because that is the door a
 * secret eventually walks through: a password, a code, a token, a "code_verifier" or a "state" would be
 * one careless line away, and what the journal must never hold is what a copy of it would otherwise give
 * away. What an act concerns beyond the person and the client is read where it happened, in the rows of
 * the thing itself.
 *
 * **The subject and not the user**: it names a person without saying anything about them, which is why it
 * is held in the clear where the provenance beside it is sealed under the data key of the tenant
 * (decisions 0011 and 0018). It is absent where the act named nobody.
 *
 * **The identifier is a UUIDv7**, so a journal sorts chronologically by its primary key and the last acts
 * of a person are a lookup rather than a sort.
 */
final class JournalEntry implements ClassifiedFieldsInterface
{
    /**
     * As long a name as the longest an event gives itself, and room for the ones to come.
     */
    public const int ACT_LENGTH = 64;

    public function __construct(
        public private(set) Uuid $id,
        public readonly Tenant $tenant,
        public readonly string $act,
        public readonly DateTimeImmutable $occurredAt,
        public readonly RequestContext $context,
        public readonly ?Subject $subject = null,
        public readonly ?string $client = null,
    ) {
        if ($act === '') {
            throw new InvalidArgumentException('An act of the journal has a name.');
        }
        if ($client === '') {
            throw new InvalidArgumentException('An act of the journal names a client or names none, never an empty one.');
        }
    }

    /**
     * An act of the provider: four facts and two names, the provenance sealed in a family of its own and the
     * subject beside it in the clear.
     *
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
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
        ];
    }
}
