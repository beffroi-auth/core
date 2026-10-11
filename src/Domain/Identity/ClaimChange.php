<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\PersonalData\ClassifiedFieldsInterface;
use Beffroi\Core\Domain\PersonalData\FieldClassificationInterface;
use Beffroi\Core\Domain\PersonalData\NoPersonalDatum;
use Beffroi\Core\Domain\PersonalData\Reason;
use Beffroi\Core\Domain\PersonalData\Sealed;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * What a claim of a person used to say, and what they made it say (issue #482): one row per change, so
 * that the question a person asks first when they read that they changed something has an answer.
 *
 * **It is not the journal.** A row of the journal is four facts and two names and no bag a caller fills,
 * because that is the door a secret eventually walks through; and what a person said of themselves is a
 * personal datum, which belongs in the row of that person and not in a second copy nobody would think to
 * erase. A value in the journal would be both at once. So the journal keeps saying that the act happened
 * (`PersonDescribedThemselves`) and this says what changed, under the rules a personal datum has: sealed
 * under the data key of its tenant (decisions 0011 and 0018) and forgotten after a duration.
 *
 * **Both values, or the absence of one.** Null on either side is a claim that said nothing: a person
 * filling one in for the first time, or taking back what they had said. A row where neither side says
 * anything is no change at all and is refused.
 *
 * **The identifier is a UUIDv7**, so a history sorts chronologically by its primary key and the last
 * changes of a person are a lookup rather than a sort.
 */
final class ClaimChange implements ClassifiedFieldsInterface
{
    /**
     * Restated from the person, as everything that hangs off a user restates it (decision 0002).
     */
    public readonly Tenant $tenant;

    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly string $claim,
        public readonly ?string $was,
        public readonly ?string $became,
        public readonly DateTimeImmutable $changedAt,
        public readonly RequestContext $context,
    ) {
        if ($claim === '') {
            throw new InvalidArgumentException('A change of a claim names the claim.');
        }
        if ($was === null && $became === null) {
            throw new InvalidArgumentException('A change of a claim is a change: a claim that said nothing and says nothing did not change.');
        }
        if ($was === $became) {
            throw new InvalidArgumentException('A change of a claim is a change: the value it had and the value it has are the same.');
        }
        $this->tenant = $user->tenant;
    }

    /**
     * What a claim of a person used to say and what they made it say are that person's own, so they are sealed as
     * the field they came from is, and the provenance beside them as every provenance is.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array
    {
        return [
            'claim' => new NoPersonalDatum('the name of the claim that changed, which is a name of OIDC Core §5.1 and never a value'),
            'was' => new Sealed('claim_change'),
            'became' => new Sealed('claim_change'),
            'changedAt' => new NoPersonalDatum(Reason::NAMES_NO_PERSON),
            'context' => new Sealed('claim_change'),
            'id' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
            'tenant' => new NoPersonalDatum(Reason::THE_TENANT),
            'user' => new NoPersonalDatum(Reason::DRAWN_AT_RANDOM),
        ];
    }
}
