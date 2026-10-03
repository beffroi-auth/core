<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

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
final class ClaimChange
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
}
