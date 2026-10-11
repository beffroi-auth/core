<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity\Claim;

use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Domain\Identity\User;
use DateTimeImmutable;

/**
 * One thing a person may be able to say about themselves (issue #452): a claim of OIDC Core §5.1, the
 * name it is known by, how it is read of a person and how that person's saying it is written.
 *
 * A class per claim and not a name in a list: what the pages offer and what a tenant may open are derived
 * from what is wired, so a claim this provider comes to hold is a class added and nothing edited. A claim
 * that is not written the way the others are says so by being its own class, which is what the phone
 * number is.
 *
 * The value is a string or nothing, "nothing" being a person taking back what they had said. What a value
 * has to look like is the model's and not this interface's: a birthdate that is not a date is refused by
 * the profile that holds it, wherever the value came from.
 */
interface ClaimInterface
{
    /**
     * The name of the claim (OIDC Core §5.1), which the policy of a tenant names and a form field carries.
     *
     * **It is the name the specification defines and never the name of the field that holds the value**
     * (issue #626): it is the name a module announces as one this provider serves, so a claim answering
     * anything else would have an operator open a name the metadata of their own instance does not carry.
     */
    public function name(): string;

    /**
     * What this provider holds of that person under this claim, null when it holds nothing.
     */
    public function of(User $user): ?string;

    /**
     * What kind of field this claim is offered as (issue #476): a page renders what the claim says, so a
     * claim added says it too and no page is edited.
     */
    public function field(): ClaimField;

    /**
     * The person once they have said this of themselves; null takes back what they had said.
     *
     * @throws InvalidArgumentException when the value is not one this claim holds
     */
    public function describe(User $user, ?string $value, DateTimeImmutable $now): void;
}
